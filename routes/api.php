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
    Route::prefix('instructor')->group(function () {
        Route::prefix('course')->group(function () {
            Route::get('index', 'Api\Instructor\CourseController@index');
            Route::post('/', 'Api\Instructor\CourseController@store');
            Route::prefix('{course_id}')->group(function () {
                Route::get('/', 'Api\Instructor\CourseController@show');
                Route::get('edit', 'Api\Instructor\CourseController@edit');
                Route::post('/', 'Api\Instructor\CourseController@update');
                Route::delete('/', 'Api\Instructor\CourseController@delete');
                Route::prefix('chapter')->group(function () {
                    Route::post('/', 'Api\Instructor\ChapterController@store');
                    Route::post('sort', 'Api\Instructor\ChapterController@sort');
                    Route::prefix('{chapter_id}')->group(function () {
                        Route::patch('/', 'Api\Instructor\ChapterController@update');
                        Route::delete('/', 'Api\Instructor\ChapterController@delete');
                        Route::prefix('lesson')->group(function () {
                            Route::post('/', 'Api\Instructor\LessonController@store');
                            Route::post('sort', 'Api\Instructor\LessonController@sort');
                            Route::prefix('{lesson_id}')->group(function () {
                                Route::patch('/','Api\Instructor\LessonController@update');
                        });
                    });
                });
            });
        });
    });
}); 
 
    // 受講生側API
    Route::prefix('course')->group(function () {
        Route::get('/', 'Api\CourseController@show');
        Route::get('index', 'Api\CourseController@index');
        Route::prefix('chapter')->group(function () {
            Route::get('/', 'Api\ChapterController@show');
        });
    });
    Route::patch('lesson_attendance', 'Api\LessonAttendanceController@update');
});
