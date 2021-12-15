<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Camp extends Model
{
    //
    public static function boot()
    {
        parent::boot();

        // here assign this team to a global user with global default role
        self::created(function (Camp $camp) {
           // get the admin user and assign roles/permissions on new team model
           CampUser::create([
                'user_id' => 1,
                'role_id' => config('status.role_Administrator'),
                'camp_id' => $camp['id']
           ]);
        });
    }

    protected $fillable = [
        'name', 'year', 'user_id', 'camp_type_id', 'group_id', 'foreign_id', 'secondsurveyopen', 'global_camp'
    ];

    protected $casts = [
        'secondsurveyopen' => 'boolean',
        'global_camp' => 'boolean',
        'demo' => 'boolean',
    ];

    public function users(){
        return $this->hasMany('App\User');
    }

    public function user(){
        return $this->belongsTo('App\User');
    } 

    public function allUsers(){
        return $this->belongsToMany('App\User', 'camp_users')->where('camp_users.role_id', '<>', config('status.role_Administrator'));
    } 

    public function participants(){
        return $this->allUsers()->where('camp_users.role_id', config('status.role_Teilnehmer'));
    } 

    public function camp_users(){
        return $this->hasMany(CampUser::class)->where('camp_users.role_id', config('status.role_Teilnehmer'));
    } 

    public function camp_type(){
        return $this->belongsTo('App\CampType');
    } 

    public function group(){
        return $this->belongsTo('App\Group');
    } 

    public function surveys()
    {
        return $this->hasManyThrough(Survey::class, CampUser::class);
    }
}
