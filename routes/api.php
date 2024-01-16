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
            Route::post('update', 'Api\Student\StudentController@update');
        });

        // 受講生-受講
        Route::prefix('attendance')->group(function () {
            Route::get('index', 'Api\Student\AttendanceController@index');
            Route::prefix('{attendance_id}')->group(function () {
                Route::prefix('course')->group(function () {
                    Route::get('/', 'Api\Student\AttendanceController@show');
                    Route::prefix('{course_id}')->group(function () {
                        Route::prefix('chapter')->group(function () {
                            // 受講生-受講-講座-チャプター
                            Route::prefix('{chapter_id}')->group(function () {
                                Route::get('/', 'Api\Student\AttendanceController@showChapter');
                            });
                        });
                    });
                });
            });
        });

        Route::get('attendance/{attendance_id}/progress', 'Api\Student\AttendanceController@progress');

        // 受講生-お知らせ
        Route::get('notification', 'Api\NotificationController@index');
        Route::prefix('attendance')->group(function () {
            Route::get('{attendance_id}', 'Api\Student\AttendanceController@show');
        });
    });

    // 受講生-レッスン受講
    Route::patch('lesson_attendance', 'Api\LessonAttendanceController@update');

    // 講師側API
    Route::middleware('instructor')->group(function () {
        // TODO 講師側APIはここに記述
        Route::prefix('instructor')->group(function () {
            Route::get('edit', 'Api\Instructor\InstructorController@edit');
            Route::post('update', 'Api\Instructor\InstructorController@update');

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
                                Route::post('/', 'Api\Instructor\LessonController@store');
                                Route::post('sort', 'Api\Instructor\LessonController@sort');
                                Route::prefix('{lesson_id}')->group(function () {
                                    Route::put('/', 'Api\Instructor\LessonController@update');
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
                Route::delete('{attendance_id}', 'Api\Instructor\AttendanceController@delete');
            });

            // 講師-生徒
            Route::prefix('student')->group(function () {
                Route::get('{student_id}', 'Api\Instructor\StudentController@show');
                Route::post('/', 'Api\Instructor\StudentController@store');
            });

            // 講師-お知らせ
            Route::prefix('notification')->group(function () {
                Route::get('index', 'Api\Instructor\NotificationController@index');
            });
        });

        // マネージャーAPI
        Route::middleware('manager')->group(function () {
            // マネージャーAPIはここに記述
            Route::prefix('manager')->group(function () {

                // マネージャー-講座
                Route::prefix('course')->group(function () {
                    Route::get('index', 'Api\Manager\CourseController@index');
                    Route::post('store', 'Api\Manager\CourseController@store');
                    Route::put('status', 'Api\Manager\CourseController@status');
                    Route::post('/', 'Api\Manager\CourseController@store');
                    Route::prefix('{course_id}')->group(function () {
                        Route::get('/', 'Api\Manager\CourseController@show');
                        Route::get('edit', 'Api\Manager\CourseController@edit');
                        Route::post('/', 'Api\Manager\CourseController@update');
                        Route::delete('/', 'Api\Manager\CourseController@delete');

                        // マネージャー-講座-生徒
                        Route::prefix('student')->group(function () {
                            Route::get('index', 'Api\Manager\StudentController@index');
                        });

                        // マネージャー-講座-チャプター
                        Route::prefix('chapter')->group(function () {
                            Route::post('/', 'Api\Manager\ChapterController@store');
                            Route::prefix('{chapter_id}')->group(function () {
                                Route::get('/', 'Api\Manager\ChapterController@show');
                                Route::patch('/', 'Api\Manager\ChapterController@update');
                                Route::delete('/', 'Api\Manager\ChapterController@delete');
                                Route::patch('status', 'Api\Manager\ChapterController@updateStatus');
                                // 講師-講座-チャプター-レッスン
                                Route::prefix('lesson')->group(function () {
                                    Route::post('sort', 'Api\Manager\LessonController@sort');
                                });
                            });
                        });
                    });
                });
            });
        });
    });
});

Route::prefix('v1')->group(function () {
    Route::prefix('student')->group(function () {
        Route::post('/', 'Api\Student\StudentController@store');
        Route::post('verification/{token}', 'Api\Student\StudentController@verifyCode');
    });
});

// 講師側API
Route::prefix('v1')->group(function () {
    Route::prefix('instructor')->group(function () {
        Route::prefix('notification')->group(function () {
            Route::prefix('{notification_id}')->group(function () {
                Route::get('/', 'Api\Instructor\NotificationController@show');
                Route::patch('/', 'Api\Instructor\NotificationController@update');
            });
        });
    });
});
