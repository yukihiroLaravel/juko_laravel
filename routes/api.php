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
            Route::get('/', 'Api\Student\StudentController@show');
            Route::post('update', 'Api\Student\StudentController@update');
        });

        // 受講生-受講
        Route::prefix('attendance')->group(function () {
            Route::get('index', 'Api\Student\AttendanceController@index');
            Route::prefix('{attendance_id}')->group(function () {
                Route::get('/', 'Api\Student\AttendanceController@show');
                Route::get('progress', 'Api\Student\AttendanceController@progress');
                Route::PUT('complete', [App\Http\Controllers\Api\Student\AttendanceController::class, 'completeAllChapters']);
                Route::prefix('course')->group(function () {
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

        // 受講生-レッスン受講
        Route::patch('lesson_attendance/{lesson_attendance_id}', [App\Http\Controllers\Api\Student\LessonAttendanceController::class, 'patchStatus']);

        // 受講生-お知らせ
        Route::prefix('notification')->group(function () {
            Route::get('index', 'Api\Student\NotificationController@index');
            Route::get('read', 'Api\Student\NotificationController@read');
            Route::get('{notification_id}', 'Api\Student\NotificationController@show');
        });
    });

    // 講師側API
    Route::middleware('instructor')->group(function () {
        // TODO 講師側APIはここに記述
        Route::prefix('instructor')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'show']);
            Route::post('update', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'update']);

            // 講師-講座
            Route::prefix('course')->group(function () {
                Route::get('index', [App\Http\Controllers\Api\Instructor\CourseController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'store']);
                Route::put('status', [App\Http\Controllers\Api\Instructor\CourseController::class, 'putStatus']);
                Route::prefix('{course_id}')->group(function () {
                    Route::get('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'show']);
                    Route::post('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'update']);
                    Route::delete('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'delete']);
                    // 講師-講座-チャプター
                    Route::prefix('chapter')->group(function () {
                        Route::post('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'store']);
                        Route::post('sort', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'sort']);
                        Route::put('status', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'putStatus']);
                        Route::patch('status', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'patchStatus']);
                        Route::delete('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'bulkDelete']);
                        Route::delete('all', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'deleteAll']);
                        Route::prefix('{chapter_id}')->group(function () {
                            Route::get('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'show']);
                            Route::patch('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'update']);
                            Route::patch('status', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'updateStatus']);
                            Route::delete('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'delete']);
                            // 講師-講座-チャプター-レッスン
                            Route::prefix('lesson')->group(function () {
                                Route::post('/', 'Api\Instructor\LessonController@store');
                                Route::post('sort', 'Api\Instructor\LessonController@sort');
                                Route::put('status', 'Api\Instructor\LessonController@putStatus');
                                Route::delete('/', 'Api\Instructor\LessonController@bulkDelete');
                                Route::delete('all', 'Api\Instructor\LessonController@deleteAll');
                                Route::prefix('{lesson_id}')->group(function () {
                                    Route::put('/', 'Api\Instructor\LessonController@put');
                                    Route::delete('/', 'Api\Instructor\LessonController@delete');
                                    Route::patch('status', 'Api\Instructor\LessonController@updateStatus');
                                    Route::patch('title', 'Api\Instructor\LessonController@updateTitle');
                                });
                            });
                        });
                    });

                    // 講師-講座-お知らせ
                    Route::prefix('notification')->group(function () {
                        Route::post('/', 'Api\Instructor\NotificationController@store');
                    });

                    // 講師-講座-受講
                    Route::prefix('attendance')->group(function () {
                        Route::prefix('status')->group(function () {
                            Route::get('/', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'show']);
                            Route::get('{period}', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'showStatus']);
                        });
                        Route::get('{period}', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'loginRate']);
                    });
                });
            });

            // 講師-受講
            Route::prefix('attendance')->group(function () {
                Route::post('/', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'store']);
                // 講師-生徒学習状況
                Route::prefix('{attendance_id}')->group(function () {
                    Route::get('status', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'status']);
                    Route::delete('/', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'delete']);
                });
            });

            // 講師-生徒
            Route::prefix('student')->group(function () {
                // 講師-講座-生徒
                Route::get('index', 'Api\Instructor\StudentController@index');
                Route::get('{student_id}', 'Api\Instructor\StudentController@show');
                Route::post('/', 'Api\Instructor\StudentController@store');
            });

            // 講師-お知らせ
            Route::prefix('notification')->group(function () {
                Route::get('index', 'Api\Instructor\NotificationController@index');
                Route::put('type/{notification_type}', 'Api\Instructor\NotificationController@updateType');
                Route::delete('/', 'Api\Instructor\NotificationController@bulkDelete');
                Route::prefix('{notification_id}')->group(function () {
                    Route::get('/', 'Api\Instructor\NotificationController@show');
                    Route::put('/', 'Api\Instructor\NotificationController@put');
                    Route::delete('/', 'Api\Instructor\NotificationController@delete');
                });
            });
        });

        // マネージャーAPI
        Route::middleware('manager')->group(function () {
            // マネージャーAPIはここに記述
            Route::prefix('manager')->group(function () {
                // マネージャー-講師
                Route::prefix('instructor')->group(function () {
                    Route::post('/', [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'store']);
                    Route::get('index', [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'index']);
                    Route::prefix('{instructor_id}')->group(function () {
                        Route::get('/', [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'show']);
                        Route::post('/', [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'update']);
                        Route::prefix('course')->group(function () {
                            Route::get('index', 'Api\Manager\Instructor\CourseController@index');
                        });
                    });
                });
                // マネージャー-講座
                Route::prefix('course')->group(function () {
                    Route::get('index', [App\Http\Controllers\Api\Manager\CourseController::class, 'index']);
                    Route::put('status', 'Api\Manager\CourseController@status');
                    Route::post('/', 'Api\Manager\CourseController@store');
                    Route::prefix('{course_id}')->group(function () {
                        Route::get('/', 'Api\Manager\CourseController@show');
                        Route::post('/', 'Api\Manager\CourseController@update');
                        Route::delete('/', 'Api\Manager\CourseController@delete');
                        // マネージャー-講座-チャプター
                        Route::prefix('chapter')->group(function () {
                            Route::post('sort', 'Api\Manager\ChapterController@sort');
                            Route::post('/', [App\Http\Controllers\Api\Manager\ChapterController::class, 'store']);
                            Route::put('status', 'Api\Manager\ChapterController@putStatus');
                            Route::delete('/', 'Api\Manager\ChapterController@bulkDelete');
                            Route::delete('all', 'Api\Manager\ChapterController@deleteAll');
                            Route::patch('status', 'Api\Manager\ChapterController@patchStatus');
                            Route::prefix('{chapter_id}')->group(function () {
                                Route::get('/', [App\Http\Controllers\Api\Manager\ChapterController::class, 'show']);
                                Route::put('/', [App\Http\Controllers\Api\Manager\ChapterController::class, 'put']);
                                Route::delete('/', [App\Http\Controllers\Api\Manager\ChapterController::class, 'delete']);
                                Route::patch('status', 'Api\Manager\ChapterController@updateStatus');
                                // マネージャー-講座-チャプター-レッスン
                                Route::prefix('lesson')->group(function () {
                                    Route::post('/', [App\Http\Controllers\Api\Manager\LessonController::class, 'store']);
                                    Route::post('sort', [App\Http\Controllers\Api\Manager\LessonController::class, 'sort']);
                                    Route::put('status', [App\Http\Controllers\Api\Manager\LessonController::class, 'putStatus']);
                                    Route::delete('/', [App\Http\Controllers\Api\Manager\LessonController::class, 'bulkDelete']);
                                    Route::delete('all', [App\Http\Controllers\Api\Manager\LessonController::class, 'deleteAll']);
                                    Route::prefix('{lesson_id}')->group(function () {
                                        Route::put('/', [App\Http\Controllers\Api\Manager\LessonController::class, 'put']);
                                        Route::delete('/', [App\Http\Controllers\Api\Manager\LessonController::class, 'delete']);
                                        Route::patch('status', [App\Http\Controllers\Api\Manager\LessonController::class, 'updateStatus']);
                                        Route::patch('title', [App\Http\Controllers\Api\Manager\LessonController::class, 'updateTitle']);
                                    });
                                });
                            });
                        });
                        Route::prefix('notification')->group(function () {
                            Route::post('/', [App\Http\Controllers\Api\Manager\NotificationController::class, 'store']);
                        });
                        //マネージャー生徒学習状況
                        Route::prefix('attendance')->group(function () {
                            Route::prefix('status')->group(function () {
                                Route::get('/', 'Api\Manager\AttendanceController@show');
                                Route::get('{period}', 'Api\Manager\AttendanceController@showStatus');
                            });
                            Route::get('{period}', 'Api\Manager\AttendanceController@loginRate');
                        });
                    });
                });
                // マネージャー-受講
                Route::prefix('attendance')->group(function () {
                    Route::post('/', 'Api\Manager\AttendanceController@store');
                    // 講師-生徒学習状況
                    Route::prefix('{attendance_id}')->group(function () {
                        Route::get('status', 'Api\Manager\AttendanceController@status');
                        Route::delete('/', 'Api\Manager\AttendanceController@delete');
                    });
                });
                // マネージャー-生徒
                Route::prefix('student')->group(function () {
                    // マネージャー-講座-生徒
                    Route::get('index', [App\Http\Controllers\Api\Manager\StudentController::class, 'index']);
                    Route::get('{student_id}', [App\Http\Controllers\Api\Manager\StudentController::class, 'show']);
                    Route::post('/', [App\Http\Controllers\Api\Manager\StudentController::class, 'store']);
                });
                // マネージャー-お知らせ
                Route::prefix('notification')->group(function () {
                    Route::get('index', [App\Http\Controllers\Api\Manager\NotificationController::class, 'index']);
                    Route::put('type/{notification_type}', [App\Http\Controllers\Api\Manager\NotificationController::class, 'updateType']);
                    Route::delete('/', [App\Http\Controllers\Api\Manager\NotificationController::class, 'bulkDelete']);

                    Route::prefix('{notification_id}')->group(function () {
                        Route::get('/', [App\Http\Controllers\Api\Manager\NotificationController::class, 'show']);
                        Route::patch('/', [App\Http\Controllers\Api\Manager\NotificationController::class, 'update']);
                        Route::delete('/', [App\Http\Controllers\Api\Manager\NotificationController::class, 'delete']);
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
    Route::prefix('instructor')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'store']);
        Route::post('verification/{token}', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'verifyCode']);
    });
});
