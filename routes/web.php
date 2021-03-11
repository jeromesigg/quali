<?php

use Illuminate\Support\Facades\Auth;

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

Route::get('/', 'HomeController@index');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');


Route::get('/survey/{id}', ['as'=>'survey.survey', 'uses'=>'SurveysController@survey']);
Route::patch('/survey/{id}', ['as'=>'survey.survey', 'uses'=>'SurveysController@update']);
Route::get('/compare/{id}', ['as'=>'survey.compare', 'uses'=>'SurveysController@compare']);
Route::patch('/compare/{id}', ['as'=>'survey.finish', 'uses'=>'SurveysController@finish']);

Route::post('/post', ['as'=>'posts.store', 'uses'=>'PostController@store']);

Route::get('/user/{id}', ['as'=>'home.user', 'uses'=>'UsersController@index']);
Route::get('/profile/{id}', ['as'=>'home.profile', 'uses'=>'UsersController@edit']);
Route::patch('/changeClassifications/{id}/{color}', ['as'=>'users.changeClassifications', 'uses'=>'UsersController@changeClassifications']);
Route::patch('/user/{id}', ['as'=>'home.user', 'uses'=>'UsersController@update']);

Route::group(['middleware' => 'campleader'], function(){

    Route::get('/admin','AdminController@index');

    Route::resource('admin/users', 'AdminUsersController');
    Route::get('users/createDataTables', ['as'=>'users.CreateDataTables','uses'=>'AdminUsersController@createDataTables']);
    Route::post('admin/users/uploadFile', 'AdminUsersController@uploadFile');
    Route::get('admin/users/download',  ['as'=>'users.download', 'uses'=>'AdminUsersController@download']);

    Route::resource('admin/answers', 'AdminAnswersController');

    Route::resource('admin/camps', 'AdminCampsController');

    Route::resource('admin/questions', 'AdminQuestionsController');
    Route::post('admin/questions/uploadFile', 'AdminQuestionsController@uploadFile');

    Route::resource('admin/surveys', 'AdminSurveysController');
    Route::get('surveys/createDataTables', ['as'=>'surveys.CreateDataTables','uses'=>'AdminSurveysController@createDataTables']);

    Route::resource('admin/chapters', 'AdminChaptersController');
    Route::resource('admin/classifications', 'AdminClassificationController');


});

Route::get('admin/run-migrations', function () {
    return Artisan::call('migrate', ["--force" => true ]);
});
