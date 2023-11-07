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
    Route::middleware('student')->group(function () {
        // 受講生
        Route::prefix('student')->group(function () {
            Route::get('edit', 'Api\Student\StudentController@edit');
            Route::patch('/', 'Api\Student\StudentController@update');
        });

        // 受講生-講座
        Route::prefix('course')->group(function () {
            Route::get('index', 'Api\CourseController@index');
            Route::get('{course_id}/progress', 'Api\CourseController@progress');
        });

        // 受講生-講座-チャプター
        Route::prefix('attendance/{attendance_id}/course/{course_id}/chapter/{chapter_id}')->group(function () {
            Route::get('/', 'Api\Student\AttendanceController@showChapter');
        });

        // 受講生-お知らせ
        Route::get('notification', 'Api\NotificationController@index');
        Route::prefix('attendance')->group(function () {
            Route::get('{attendance_id}', 'Api\Student\AttendanceController@show');
            });
        });

        // 受講生-レッスン受講
        Route::patch('lesson_attendance', 'Api\LessonAttendanceController@update');
    });

    // 講師側API
    Route::middleware('instructor')->group(function () {
        // TODO 講師側APIはここに記述
        Route::prefix('instructor')->group(function () {
            Route::get('edit', 'Api\Instructor\InstructorController@edit');
            // 講師-講座
            Route::prefix('course')->group(function () {
                Route::get('index', 'Api\Instructor\CourseController@index');
                Route::post('/', 'Api\Instructor\CourseController@store');
                Route::put('status', 'Api\Instructor\CourseController@putStatus');
                Route::prefix('{course_id}')->group(function () {
                    Route::get('/', 'Api\Instructor\CourseController@show');
                    Route::get('edit', 'Api\Instructor\CourseController@edit');
                    Route::post('/', 'Api\Instructor\CourseController@update');
                    Route::delete('/', 'Api\Instructor\CourseController@delete');
                    // 講師-講座-チャプター
                    Route::prefix('chapter')->group(function () {
                        Route::post('/', 'Api\Instructor\ChapterController@store');
                        Route::post('sort', 'Api\Instructor\ChapterController@sort');
                        Route::put('status', 'Api\Instructor\ChapterController@putStatus');
                        Route::prefix('{chapter_id}')->group(function () {
                            Route::get('/', 'Api\Instructor\ChapterController@show');
                            Route::patch('/', 'Api\Instructor\ChapterController@update');
                            Route::patch('status', 'Api\Instructor\ChapterController@updateStatus');
                            Route::delete('/', 'Api\Instructor\ChapterController@delete');
                            // 講師-講座-チャプター-レッスン
                            Route::prefix('lesson')->group(function () {
                                Route::post('sort', 'Api\Instructor\LessonController@sort');
                                Route::prefix('{lesson_id}')->group(function () {
                                    Route::post('/', 'Api\Instructor\LessonController@store');
                                    Route::patch('/', 'Api\Instructor\LessonController@update');
                                    Route::delete('/', 'Api\Instructor\LessonController@delete');
                                });
                            });
                        });
                    });

                    // 講師-講座-生徒
                    Route::prefix('student')->group(function () {
                        Route::get('index', 'Api\Instructor\StudentController@index');
                    });

                    // 講師-講座-お知らせ
                    Route::prefix('notification')->group(function () {
                        Route::post('/', 'Api\Instructor\NotificationController@store');
                    });

                    // 講師-講座-受講
                    Route::prefix('attendance')->group(function () {
                        Route::get('status', 'Api\Instructor\AttendanceController@show');
                        Route::get('{period}', 'Api\Instructor\AttendanceController@loginRate');
                    });
                });
            });

            // 講師-受講
            Route::prefix('attendance')->group(function () {
                Route::post('/', 'Api\Instructor\AttendanceController@store');
            });

            // 講師-生徒
            Route::prefix('student')->group(function () {
                Route::get('{student_id}', 'Api\Instructor\StudentController@show');
                Route::post('/', 'Api\Instructor\StudentController@store');
            });

            // 講師
            Route::prefix('{instructor_id}')->group(function () {
                Route::post('/', 'Api\Instructor\InstructorController@update');
            });
        });
    });
});

Route::prefix('v1')->group(function () {
    Route::post('student', 'Api\Student\StudentController@store');
});

// 講師側API
Route::prefix('instructor')->group(function () {
    Route::get('edit', 'Api\Instructor\InstructorController@edit');
    Route::prefix('notification')->group(function () {
        Route::get('index', 'Api\Instructor\NotificationController@index');
        Route::prefix('{notification_id}')->group(function () {
            Route::get('/', 'Api\Instructor\NotificationController@show');
            Route::patch('/', 'Api\Instructor\NotificationController@update');
        });
    });
});
