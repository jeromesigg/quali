<?php

namespace App\Http\Controllers;

use App\Camp;
use App\User;
use App\Group;
use App\CampType;
use App\CampUser;
use App\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $users =[];
        $camptypes = CampType::pluck('name','id')->all();
        $groups = Group::where('campgroup',true)->pluck('name','id')->all();
        return view('home.camps.create', compact('users', 'camptypes', 'groups'));
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
        $input = $request->all();
        $user = User::findOrFail(Auth::user()->id);
        $input['user_id'] = $user->id;
        $input['global_camp'] = false;
        $camp = Camp::create($input);
        $user->update(['camp_id' => $camp->id, 'role_id' => config('status.role_Kursleiter')]);
        CampUser::create([
            'user_id' => $user->id,
            'camp_id' => $camp->id,
            'role_id' => config('status.role_Kursleiter')]);

        return redirect('home');
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
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Camp $camp)
    {
        //
       Helper::updateCamp(Auth::user(), $camp);
       return redirect('/home');
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
    }
}
