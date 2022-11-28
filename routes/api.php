<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    Route::get('courses', 'Api\CourseController@index');
<<<<<<< HEAD
=======
    Route::get('course/chapter', 'Api\ChapterController@index');
    Route::get('course/chapter/lesson', 'Api\LessonController@index');
>>>>>>> feature/yuta/jka-65/lesson_api
});
