<?php

namespace App\Http\Controllers;

use App\User;
use App\Survey;
use App\CampUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    public function index(){
        $user = Auth::user();
        $surveys = $user->camp->surveys()->with(['chapters.questions.answer_first','chapters.questions.answer_second','chapters.questions.answer_leader', 'campuser.user', 'chapters.questions.question'])
           ->get()->sortBy('campuser.user.username')->values();
        $surveys_all = $user->camp->surveys()->count();
        $surveys_1offen = $user->camp->surveys()->where('survey_status_id', '>', config('status.survey_1offen'))->count();
        $surveys_2offen = $user->camp->surveys()->where('survey_status_id', '>', config('status.survey_2offen'))->count();
        $surveys_fertig = $user->camp->surveys()->where('survey_status_id', config('status.survey_fertig'))->count();
        return view('admin/index', compact('user','surveys', 'surveys_all', 'surveys_1offen', 'surveys_2offen', 'surveys_fertig'));
    }

    public function changes(){
        $user = Auth::user();
        return view('admin/changes', compact('user'));
    }
}
