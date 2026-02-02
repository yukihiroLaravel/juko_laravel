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

Route::middleware('auth:sanctum')->get('/user',
    function (Request $request) {
        $user = $request->user();

        return [
            ...$user->toArray(),
            'role' => match (true) {
                $user instanceof \App\Model\Student => 'student',
                $user instanceof \App\Model\Instructor => 'instructor',
                default => 'unknown',
            },
        ];
    }
);

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // 受講生側API
    Route::middleware('student')->group(function () {
        // 受講生
        Route::prefix('student')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Student\StudentController::class, 'show']);
            Route::post('update', [App\Http\Controllers\Api\Student\StudentController::class, 'update']);
        });

        // 受講生-受講
        Route::prefix('attendance')->group(function () {
            Route::get('index', [App\Http\Controllers\Api\Student\AttendanceController::class, 'index']);
            Route::prefix('{attendance_id}')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\Student\AttendanceController::class, 'show']);
                Route::get('progress', [App\Http\Controllers\Api\Student\AttendanceController::class, 'progress']);
                Route::PUT('complete', [App\Http\Controllers\Api\Student\AttendanceController::class, 'completeAllChapters']);
                Route::put('chapter/{chapter_id}/complete', [App\Http\Controllers\Api\Student\AttendanceController::class, 'completeAllLessons']);
            });
        });

        // 受講生-レッスン受講
        Route::patch('lesson_attendance/{lesson_attendance_id}', [App\Http\Controllers\Api\Student\LessonAttendanceController::class, 'patchStatus']);

        // 受講生-お知らせ
        Route::prefix('notification')->group(function () {
            Route::get('index', [App\Http\Controllers\Api\Student\NotificationController::class, 'index']);
            Route::post('mark-read', [App\Http\Controllers\Api\Student\NotificationController::class, 'markRead']);
            Route::get('{notification_id}', [App\Http\Controllers\Api\Student\NotificationController::class, 'show']);
        });
    });

    // 講師側API
    Route::middleware('instructor')->group(function () {
        // TODO 講師側APIはここに記述
        Route::prefix('instructor')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'show']);
            Route::post('update', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'update']);

            // 講師-講座タグ一覧
            Route::get('tag/index', [App\Http\Controllers\Api\Instructor\TagController::class, 'index']);

            // 講師-講座
            Route::prefix('course')->group(function () {
                Route::get('index', [App\Http\Controllers\Api\Instructor\CourseController::class, 'index']);
                Route::post('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'store']);
                Route::put('status', [App\Http\Controllers\Api\Instructor\CourseController::class, 'putStatus']);
                Route::get('tag/index', [App\Http\Controllers\Api\Instructor\Course\TagController::class, 'index']);
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
                            Route::put('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'put']);
                            // 講師-講座-チャプター-レッスン
                            Route::prefix('lesson')->group(function () {
                                Route::post('/', [App\Http\Controllers\Api\Instructor\LessonController::class, 'store']);
                                Route::post('sort', [App\Http\Controllers\Api\Instructor\LessonController::class, 'sort']);
                                Route::put('status', [App\Http\Controllers\Api\Instructor\LessonController::class, 'putStatus']);
                                Route::delete('/', [App\Http\Controllers\Api\Instructor\LessonController::class, 'bulkDelete']);
                                Route::delete('all', [App\Http\Controllers\Api\Instructor\LessonController::class, 'deleteAll']);
                                Route::prefix('{lesson_id}')->group(function () {
                                    Route::put('/', [App\Http\Controllers\Api\Instructor\LessonController::class, 'put']);
                                    Route::delete('/', [App\Http\Controllers\Api\Instructor\LessonController::class, 'delete']);
                                    Route::patch('status', [App\Http\Controllers\Api\Instructor\LessonController::class, 'updateStatus']);
                                    Route::patch('title', [App\Http\Controllers\Api\Instructor\LessonController::class, 'updateTitle']);
                                });
                            });
                        });
                    });

                    // 講師-講座-お知らせ
                    Route::prefix('notification')->group(function () {
                        Route::post('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'store']);
                    });

                    // 講師-講座-受講
                    Route::prefix('attendance')->group(function () {
                        Route::prefix('status')->group(function () {
                            Route::get('{period}', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'showStatus']);
                        });
                        Route::get('{period}', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'loginRate']);
                    });
                });
            });

            // 講師-タグ
            Route::prefix('tag')->group(function () {
                Route::post('/', [App\Http\Controllers\Api\Instructor\TagController::class, 'store']);
                Route::prefix('{tag_id}')->group(function () {
                    Route::get('/', [App\Http\Controllers\Api\Instructor\TagController::class, 'show']);
                    Route::put('/', [App\Http\Controllers\Api\Instructor\TagController::class, 'put']);
                    Route::delete('/', [App\Http\Controllers\Api\Instructor\TagController::class, 'delete']);
                });
            });

            // 講師-受講
            Route::prefix('attendance')->group(function () {
                Route::post('/', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'store']);
                // 講師-生徒学習状況
                Route::prefix('{attendance_id}')->group(function () {
                    Route::get('/', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'show']);
                    Route::get('status', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'status']);
                    Route::delete('/', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'delete']);
                });
            });

            // 講師-生徒
            Route::prefix('student')->group(function () {
                // 講師-講座-生徒
                Route::get('index', [App\Http\Controllers\Api\Instructor\StudentController::class, 'index']);
                Route::get('{student_id}', [App\Http\Controllers\Api\Instructor\StudentController::class, 'show']);
                Route::post('/', [App\Http\Controllers\Api\Instructor\StudentController::class, 'store']);
            });

            // 講師-お知らせ
            Route::prefix('notification')->group(function () {
                Route::get('index', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'index']);
                Route::prefix('type')->group(function () {
                    Route::put('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'updateType']);
                    Route::put('all', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'updateTypeAll']);
                });
                Route::prefix('status')->group(function () {
                    Route::put('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'putStatus']);
                    Route::put('all', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'putStatusAll']);
                });
                Route::delete('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'bulkDelete']);
                Route::prefix('{notification_id}')->group(function () {
                    Route::get('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'show']);
                    Route::put('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'put']);
                    Route::delete('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'delete']);
                });
            });
        });

        // マネージャーAPI
        Route::middleware('manager')->group(function () {
            // マネージャーAPIはここに記述
            Route::prefix('manager')->group(function () {
                // マネージャー-タグ
                Route::prefix('tag')->group(function () {
                    Route::prefix('{tag_id}')->group(function () {
                        Route::put('/', [App\Http\Controllers\Api\Manager\TagController::class, 'put']);
                        Route::delete('/', [App\Http\Controllers\Api\Manager\TagController::class, 'delete']);
                        Route::get('/', [App\Http\Controllers\Api\Manager\TagController::class, 'show']);
                    });
                });
                // マネージャー-講師
                Route::prefix('instructor')->group(function () {
                    Route::post('/', [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'store']);
                    Route::get('index', [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'index']);
                    Route::prefix('{instructor_id}')->group(function () {
                        Route::get('/', [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'show']);
                        Route::post('/', [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'update']);
                        Route::prefix('course')->group(function () {
                            Route::get('index', [App\Http\Controllers\Api\Manager\Instructor\CourseController::class, 'index']);
                        });
                    });
                });
                // マネージャー-講座
                Route::prefix('course')->group(function () {
                    Route::get('index', [App\Http\Controllers\Api\Manager\CourseController::class, 'index']);
                    Route::put('status', [App\Http\Controllers\Api\Manager\CourseController::class, 'putStatus']);
                    Route::post('/', [App\Http\Controllers\Api\Manager\CourseController::class, 'store']);
                    Route::post('deadline/clear-all', [App\Http\Controllers\Api\Manager\CourseDeadlineController::class, 'clearAll']);
                    Route::prefix('{course_id}')->group(function () {
                        Route::get('/', [App\Http\Controllers\Api\Manager\CourseController::class, 'show']);
                        Route::post('/', [App\Http\Controllers\Api\Manager\CourseController::class, 'update']);
                        // マネージャー-講座-チャプター
                        Route::prefix('chapter')->group(function () {
                            Route::prefix('{chapter_id}')->group(function () {
                                Route::delete('/', [App\Http\Controllers\Api\Manager\ChapterController::class, 'delete']);
                                Route::patch('status', [App\Http\Controllers\Api\Manager\ChapterController::class, 'updateStatus']);

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

                        // マネージャー生徒学習状況
                        Route::prefix('attendance')->group(function () {
                            Route::prefix('status')->group(function () {
                                Route::get('{period}', [App\Http\Controllers\Api\Manager\AttendanceController::class, 'showStatus']);
                            });
                            Route::get('{period}', [App\Http\Controllers\Api\Manager\AttendanceController::class, 'loginRate']);
                        });
                    });
                    // マネージャー講師-タグ
                    Route::prefix('tag')->group(function () {
                        Route::get('index', [App\Http\Controllers\Api\Manager\TagController::class, 'index']);
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
                    Route::prefix('type')->group(function () {
                        Route::put('/', [App\Http\Controllers\Api\Manager\NotificationController::class, 'updateType']);
                        Route::put('all', [App\Http\Controllers\Api\Manager\NotificationController::class, 'updateTypeAll']);
                    });
                    Route::put('type/{notification_type}', [App\Http\Controllers\Api\Manager\NotificationController::class, 'updateType']);
                    Route::put('status', [App\Http\Controllers\Api\Manager\NotificationController::class, 'putStatus']);
                    Route::put('status/all', [App\Http\Controllers\Api\Manager\NotificationController::class, 'putStatusAll']);

                    Route::prefix('{notification_id}')->group(function () {
                        Route::put('/', [App\Http\Controllers\Api\Manager\NotificationController::class, 'put']);
                        Route::delete('/', [App\Http\Controllers\Api\Manager\NotificationController::class, 'delete']);
                    });
                });
            });
        });
    });
});

Route::prefix('v1')->group(function () {
    Route::prefix('student')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\Student\StudentController::class, 'store']);
        Route::post('verification/{token}', [App\Http\Controllers\Api\Student\StudentController::class, 'verifyCode']);
    });
    Route::prefix('instructor')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'store']);
        Route::post('verification/{token}', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'verifyCode']);
    });
});
