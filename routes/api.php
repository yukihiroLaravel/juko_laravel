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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {
    Route::prefix('courses')->group(function () {
        Route::get('/', 'Api\CourseController@index');
        Route::post('register', 'Api\CourseController@store');
    });
    Route::prefix('course')->group(function () {
        Route::get('/', 'Api\CourseController@show');
        Route::prefix('chapter')->group(function () {
            Route::get('/', 'Api\ChapterController@show');
        });
    });
    Route::patch('lesson_attendance', 'Api\LessonAttendanceController@update');
});

