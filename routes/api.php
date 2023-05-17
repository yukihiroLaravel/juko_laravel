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
    // 講師側API
    Route::prefix('instructor')->group(function(){
        Route::get('{instructor_id}/courses', 'Api\Instructor\CourseController@index');
        Route::prefix('course')->group(function () {
            Route::get('{course_id}', 'Api\Instructor\CourseController@show');
            Route::post('{course_id}','Api\Instructor\CourseController@update');
            Route::delete('{course_id}', 'Api\Instructor\CourseController@delete');
            Route::prefix('chapter')->group(function () {
                Route::post('sort','Api\Instructor\ChapterController@sort');
            });
        });
    });

    // 受講生側API
    Route::prefix('courses')->group(function () {
        Route::get('/', 'Api\CourseController@index');
    });
    Route::prefix('course')->group(function () {
        Route::get('/', 'Api\CourseController@show');
        Route::prefix('chapter')->group(function () {
            Route::get('/', 'Api\ChapterController@show');
        });
        Route::get('{course_id}/edit','Api\CourseController@edit');
    });
    Route::patch('lesson_attendance', 'Api\LessonAttendanceController@update');
});
