<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    //
    protected $fillable = [
    'name', 'number', 'competence', 'chapter_id'
    ];

    public function chapter(){
        return $this->belongsTo('App\Chapter');
    }
}
