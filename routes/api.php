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

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // 受講生側API
    Route::prefix('course')->group(function () {
        Route::get('index', 'Api\CourseController@index');
        Route::get('/', 'Api\CourseController@show');
        Route::get('{course_id}/progress', 'Api\CourseController@progress');
        Route::prefix('chapter')->group(function () {
            Route::get('/', 'Api\ChapterController@show');
        });
    });
    Route::get('student/edit', 'Api\Student\StudentController@edit');
    Route::get('notification', 'Api\NotificationController@index');
});

Route::prefix('v1')->group(function () {
    // 講師側API
    Route::prefix('instructor')->group(function () {
        Route::get('edit', 'Api\Instructor\InstructorController@edit');
        Route::get('student/{student_id}', 'Api\Instructor\StudentController@show');
        Route::prefix('attendance')->group(function () {
            Route::post('/', 'Api\Instructor\AttendanceController@store');
        });
        Route::patch('/', 'Api\Instructor\InstructorController@update');
        Route::patch('notification/{notification_id}', 'Api\Instructor\NotificationController@update');
        Route::post('student', 'Api\Instructor\StudentController@store');
        Route::prefix('notification')->group(function () {
            Route::get('index', 'Api\Instructor\NotificationController@index');
            Route::prefix('{notification_id}')->group(function () {
                Route::get('/', 'Api\Instructor\NotificationController@show');
            });
        });
        Route::prefix('course')->group(function () {
            Route::get('index', 'Api\Instructor\CourseController@index');
            Route::post('/', 'Api\Instructor\CourseController@store');
            Route::prefix('{course_id}')->group(function () {
                Route::get('student/index', 'Api\Instructor\StudentController@index');
                Route::get('/', 'Api\Instructor\CourseController@show');
                Route::get('edit', 'Api\Instructor\CourseController@edit');
                Route::post('/', 'Api\Instructor\CourseController@update');
                Route::delete('/', 'Api\Instructor\CourseController@delete');
                Route::put('status', 'Api\Instructor\CourseController@putStatus');
                Route::prefix('notification')->group(function () {
                    Route::post('/', 'Api\Instructor\NotificationController@store');
                });
                Route::prefix('attendance')->group(function () {
                    Route::get('status', 'Api\Instructor\AttendanceController@show');
                });
                Route::prefix('chapter')->group(function () {
                    Route::post('/', 'Api\Instructor\ChapterController@store');
                    Route::post('sort', 'Api\Instructor\ChapterController@sort');
                    Route::prefix('{chapter_id}')->group(function () {
                        Route::get('/', 'Api\Instructor\ChapterController@show');
                        Route::patch('/', 'Api\Instructor\ChapterController@update');
                        Route::delete('/', 'Api\Instructor\ChapterController@delete');
                        Route::prefix('lesson')->group(function () {
                            Route::post('/', 'Api\Instructor\LessonController@store');
                            Route::post('sort', 'Api\Instructor\LessonController@sort');
                            Route::prefix('{lesson_id}')->group(function () {
                                Route::delete('/', 'Api\Instructor\LessonController@delete');
                                Route::patch('/', 'Api\Instructor\LessonController@update');
                            });
                        });
                    });
                });
            });
        });
    });

    Route::patch('lesson_attendance', 'Api\LessonAttendanceController@update');

    Route::prefix('student')->group(function () {
        Route::patch('/', 'Api\Student\StudentController@update');
    });
});
