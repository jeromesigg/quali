<?php

namespace App\Http\Controllers;

use App\Camp;
use App\Role;
use App\User;
use App\Group;
use App\Classification;
use Illuminate\Support\Str;
use App\Imports\UsersImport;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

class AdminUsersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.users.index');
    }

    public function createDataTables()
    {
        //
        if(!Auth::user()->isAdmin()){
            $camp = Auth::user()->camp;
            $users = User::where('camp_id', $camp['id'])->where('is_active', true)->get();
        }
        else{
            $users = User::where('is_active', true)->get();
        }

        return DataTables::of($users)
            ->addColumn('picture', function($user) {
                $path = $user->avatar ? $user->avatar : 'https://placehold.it/50x50';
                return '<a href='.\URL::route('home.profile', $user['slug']).' title="Zum Profil"><img height="50" src="'.$path .'" alt=""></a>';
            })
            ->addColumn('user', function($user) {
                return '<a name='.$user['username'].' title="Person bearbeiten" href='.\URL::route('users.edit', $user['slug']).'>'.$user['username'].'</a>';
            })
            ->addColumn('role', function (User $user) {
                return $user->role ? $user->role['name'] : '';})
            ->addColumn('leader', function (User $user) {
                return $user->leader ? $user->leader['username'] : '';})
                ->addColumn('classification', function (User $user) {
                    return $user->classification ? $user->classification['name'] : '';})
            ->addColumn('camp', function (User $user) {
                return $user->camp ? $user->camp['name'] : '';})
            ->addColumn('password_changed', function (User $user) {
                if(isset($user->password_change_at)){
                    return 'Ja';
                }
                else{
                    return 'Nein';
                }})
            ->rawColumns(['picture','user'])
            ->make(true);
    }

    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $aktUser = Auth::user();
        if( $aktUser->isAdmin()){
            $roles = Role::pluck('name','id')->all();
            $leaders = User::where('role_id',config('status.role_Gruppenleiter'))->where('is_active', true)->pluck('username','id')->all();
        }
        else{
            $roles = Role::where('id','>',config('status.role_Administrator'))->pluck('name','id')->all(); 
            $leaders = User::where('role_id',config('status.role_Gruppenleiter'))->where('is_active', true)->where('camp_id',$aktUser->camp->id)->pluck('username','id')->all();
        }
        $classifications = Classification::pluck('name','id')->all();
        return view('admin.users.create', compact('roles', 'leaders', 'classifications'));
    }

    public function import(Request $request){
        $aktUser = Auth::user();
        $camp = $aktUser->camp;
        if($camp->foreign_id && $camp->group){
            $input = $request->all();
            $response = Curl::to('https://db.cevi.ch/users/sign_in.json')
                ->withData( 
                    array( 
                        'person[email]' => $input['name'],
                        'person[password]' => $input['password'] ))
                ->post();
            $response = json_decode($response);
            if(!isset($response->error)){
                $aktUser_id = $response->people[0]->id;
                $token = $response->people[0]->authentication_token;
                $response = Curl::to('https://db.cevi.ch/groups/' .$camp->group['foreign_id']. '/events/' .$camp['foreign_id']. '/participations.json')
                    ->withData( 
                        array( 
                            'user_email' => $input['name'],
                            'user_token' => $token))
                    ->get();
                $response = json_decode($response);
                $participants = $response->event_participations;
                foreach($participants as $participant){
                    if ($participant->roles[0]->type === "Event::Course::Role::Participant" ||
                            $participant->roles[0]->type === "Event::Role::AssistantLeader" ||
                            $participant->roles[0]->type === "Event::Role::Leader"){
                        $response = Curl::to('https://db.cevi.ch/groups/' . $participant->ortsgruppe_id . '.json')
                            ->withData( 
                                array( 
                                    'user_email' => $input['name'],
                                    'user_token' => $token))
                            ->get();
                        $response = json_decode($response);
                        $group_response = $response->groups;
                        $insertData = array(
                            
                            "shortname" => $group_response[0]->short_name,
                            "name" => $group_response[0]->name,
                            "foreign_id" => $group_response[0]->id,
                            "campgroup" => false);

                        $group = Group::firstOrCreate(['foreign_id' => $group_response[0]->id], $insertData);

                        if ($participant->links->person != $aktUser_id){
                            $username = $participant->nickname . '@' . $group['shortname'];
                            switch($participant->roles[0]->type){
                                case  'Event::Course::Role::Participant':
                                    $role_id = config('status.role_Teilnehmer');
                                    break;
                                case  'Event::Role::AssistantLeader':
                                    $role_id = config('status.role_Gruppenleiter');
                                    break;
                                case  'Event::Role::Leader':
                                    $role_id = config('status.role_Kursleiter');
                                    break;

                            }
                            $insertData = array(
                                
                                "username" =>  $username,
                                "slug" => $username,
                                "password" => bcrypt(mb_strtolower($username)),
                                "role_id" => $role_id,
                                "is_active" => true,
                                "camp_id" => $camp['id'],
                                'classification_id' => config('status.classification_green'));

                            $user = User::where(DB::raw('LOWER(`username`) LIKE "' . mb_strtolower($username). '"'))->Orwhere('foreign_id', $participant->links->person)->first();
                            if(!$user){
                                $user = User::create($insertData);
                            }
                        }
                        else{
                            $user = Auth::user();
                        }
                        if(!$user->avatar){
                            $user->update(['avatar' => 'https://db.cevi.ch'. $participant->picture->url]);     
                        }
                        if(!$user->group_id)  {
                            $user->update(['group_id' => $group->id]);   

                        }  
                        if(!$user->foreign_id)  {
                            $user->update(['foreign_id' => $participant->links->person]);   
                        }   
                    }            
                }
            }
                        
        }
        return true;
    }

    public function uploadFile(Request $request){
        if($request->hasFile('csv_file')){
          
            $array = (new UsersImport)->toArray(request()->file('csv_file'));
            $importData_arr = $array[0];
            
      
            // Insert to MySQL database
            $user = Auth::user();
            foreach($importData_arr as $importData){

                $username = mb_strtolower($importData['username']);

                if($importData['rollen']==='K'){
                    
                    $insertData = array(
                    
                        "username"=> $username,
                        "password"=>bcrypt($importData['password']),
                        "role_id"=>config('status.role_Kursleiter'),
                        "is_active"=>true,
                        "camp_id"=>$user['camp_id'],
                        'classification_id' => config('status.classification_green'));

                    User::firstOrCreate(['username' => $username], $insertData);

                }
                elseif($importData['rollen']==='G'){
                    

                    $insertData = array(
                    
                        "username"=> $username,
                        "password"=>bcrypt($importData['password']),
                        "role_id"=>config('status.role_Gruppenleiter'),
                        "is_active"=>true,
                        "camp_id"=>$user['camp_id'],
                        'classification_id' => config('status.classification_green'));

                    User::firstOrCreate(['username' => $username], $insertData);
                }

    
            }
            foreach($importData_arr as $importData){

                $username = mb_strtolower($importData['username']);

                if($importData['rollen']==='T'){
                    
                    $user = Auth::user();
                    $leader = User::where('username', $importData['leiter'])->first();

                    $insertData = array(
                    
                        "username"=> $username,
                        "password"=>bcrypt($importData['password']),
                        "role_id"=>config('status.role_Teilnehmer'),
                        "is_active"=>true,
                        "camp_id"=>$user['camp_id'],
                        "leader_id"=>$leader['id'],
                        'classification_id' => config('status.classification_green'));

                    User::firstOrCreate(['username' => $username], $insertData);
                }
    
            }
        }
        
        return redirect()->action('AdminUsersController@index');

        
    }

    public function download(){
        return Storage::download('file.jpg', 'Teilnehmerliste.xlsx');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $this->validate($request, [
            'username' => 'required|unique:users|max:255',
        ]);

        $aktUser = Auth::user();
        if(trim($request->password) == ''){
            $input = $request->except('password');
        }
        else{
            $input = $request->all();
            $input['password'] = bcrypt($request->password);
        }

        if(!$aktUser->isAdmin()){
            $camp = $aktUser->camp;
            $input['camp_id'] = $camp['id'];
            if($file = $request->file('avatar')){
                if($input['cropped_photo_id']){
                    $save_path = 'images/'.$camp['name'];
                    if (!file_exists($save_path)) {
                        mkdir($save_path, 666, true);
                    }
                    $name = time() . str_replace(' ', '', $file->getClientOriginalName());
                    Image::make($input['cropped_photo_id'])->save($save_path.'/'.$name, 80);  
                    $input['avatar'] = '/'.$save_path.'/'.$name;
                }
            }
        }
        $input['slug'] = Str::slug($input['username']);

        User::create($input);

        return redirect('/admin/users/create');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
        // $user = User::findOrFail($id);
        $aktUser = Auth::user();
        $roles = Role::pluck('name','id')->all();
        $leaders = User::where('role_id', config('status.role_Gruppenleiter'))->where('camp_id',$aktUser->camp->id)->pluck('username','id')->all();
        $classifications = Classification::pluck('name','id')->all();
        return view('admin.users.edit', compact('user','roles', 'leaders', 'classifications'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $this->validate($request, [
            'username' => 'required|unique:users|max:255',
        ]);
        $user = User::findOrFail($id);
        $aktuser = Auth::user();

        if(trim($request->password) == ''){
            $input = $request->except('password');
        }
        else{
            $input = $request->all();
            $input['password'] = bcrypt($request->password);
        }
        if($file = $request->file('avatar')){
            if($input['cropped_photo_id']){
                $save_path = 'images/'.$aktuser->camp['name'];
                if (!file_exists($save_path)) {
                    mkdir($save_path, 666, true);
                }
                $name = time() . str_replace(' ', '', $file->getClientOriginalName());
                Image::make($input['cropped_photo_id'])->save($save_path.'/'.$name, 80);  
                $input['avatar'] = '/'.$save_path.'/'.$name;
            }
        }
        $input['slug'] = Str::slug($input['username']);

        $user->update($input);

        return redirect('/admin/users');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        User::findOrFail($id)->delete();
        return redirect('/admin/users');
    }
}
