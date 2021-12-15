<?php

use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', 'HomeController@index');

Route::get('/', function () {
    return view('welcome');
});

Route::get('/loginTN',function(){
    $user = User::where('username','tn11@demo')->first();
    Auth::login($user);
    return redirect('home');
});

Route::get('/loginLeiter',function(){
    $user = User::where('username','leiter1@demo')->first();
    Auth::login($user);
    return redirect('home');
});

Route::get('/loginKursleiter',function(){
    $user = User::where('username','kursleiter@demo')->first();
    Auth::login($user);
    return redirect('home');
});

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();

    return redirect('/');
})->middleware(['auth', 'signed'])->name('verification.verify');

Auth::routes(['verify' => true]);

Route::group(['middleware' => 'verified'], function(){

    Route::get('/home', 'HomeController@index');


    Route::get('/survey/{survey}', ['as'=>'survey.survey', 'uses'=>'SurveysController@survey']);
    Route::patch('/survey/{survey}', ['as'=>'survey.update', 'uses'=>'SurveysController@update']);
    Route::get('/compare/{survey}', ['as'=>'survey.compare', 'uses'=>'SurveysController@compare']);
    Route::patch('/compare/{id}', ['as'=>'survey.finish', 'uses'=>'SurveysController@finish']);
    Route::get('/compare/{survey}/downloadPDF', ['as'=>'survey.downloadPDF','uses'=>'SurveysController@downloadPDF']);

    Route::resource('/post', 'PostController');

    Route::get('/user/{user}', ['as'=>'home.user', 'uses'=>'UsersController@index']);
    Route::get('/profile/{user}', ['as'=>'home.profile', 'uses'=>'UsersController@edit']);
    Route::patch('/changeClassifications/{id}/{color}', ['as'=>'users.changeClassifications', 'uses'=>'UsersController@changeClassifications']);
    Route::patch('/user/{id}', ['as'=>'home.update', 'uses'=>'UsersController@update']);


    Route::resource('/camps', 'CampsController', [ 'as' => 'home'])->only(['create', 'store', 'update']);

    Route::get('admin/users/searchajaxuser', ['as'=>'searchajaxuser','uses'=>'AdminUsersController@searchResponseUser']);

    Route::group(['middleware' => 'campleader'], function(){

        Route::get('/admin','AdminController@index');

        Route::get('/admin/changes','AdminController@changes');

        Route::resource('admin/users', 'AdminUsersController');
        Route::get('users/createDataTables', ['as'=>'users.CreateDataTables','uses'=>'AdminUsersController@createDataTables']);
        Route::post('admin/users/uploadFile', 'AdminUsersController@uploadFile');
        Route::get('admin/users/download',  ['as'=>'users.download', 'uses'=>'AdminUsersController@download']);
        Route::post('admin/users/import',  ['as'=>'users.import', 'uses'=>'AdminUsersController@import']);
        Route::post('admin/users/add',  ['as'=>'users.add', 'uses'=>'AdminUsersController@add']);

        Route::resource('admin/answers', 'AdminAnswersController');

        Route::post('admin/camps/opensurvey', ['as'=>'admin.camps.opensurvey','uses'=>'AdminCampsController@opensurvey']);
        Route::resource('admin/camps', 'AdminCampsController', [ 'as' => 'admin']);

        Route::resource('admin/questions', 'AdminQuestionsController');
        Route::post('admin/questions/uploadFile', 'AdminQuestionsController@uploadFile');

        Route::resource('admin/surveys', 'AdminSurveysController');
        Route::get('surveys/createDataTables', ['as'=>'surveys.CreateDataTables','uses'=>'AdminSurveysController@createDataTables']);

        Route::resource('admin/chapters', 'AdminChaptersController');
        Route::resource('admin/competences', 'AdminCompetencesController');
        Route::resource('admin/classifications', 'AdminClassificationController');


    });

});

Route::get('admin/run-migrations', function () {
    return Artisan::call('migrate', ["--force" => true ]);
});

Route::get('admin/run-deployment', function () {
    Artisan::call('optimize:clear');
    return true;
});

Route::get('admin/run-migrations-seed', function () {
    return Artisan::call('migrate:refresh --seed', ["--force" => true ]);
});