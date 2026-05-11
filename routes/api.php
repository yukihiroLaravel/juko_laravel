<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::middleware('student')->name('student.')->group(function () {
        // 受講生
        Route::prefix('student')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Student\StudentController::class, 'show'])->name('show');
            Route::post('update', [App\Http\Controllers\Api\Student\StudentController::class, 'update'])->name('update');
            Route::get('learning-history', [App\Http\Controllers\Api\Student\LearningHistoryController::class, 'index'])->name('learning-history.index');
            Route::get('login-streak', [App\Http\Controllers\Api\Student\LoginController::class, 'loginStreak'])->name('login-streak');
        });

        // 受講生-受講
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('index', [App\Http\Controllers\Api\Student\AttendanceController::class, 'index'])->name('index');
            Route::prefix('{attendance_id}')->group(function () {
                Route::get('/', [App\Http\Controllers\Api\Student\AttendanceController::class, 'show'])->name('show');
                Route::get('progress', [App\Http\Controllers\Api\Student\AttendanceController::class, 'progress'])->name('progress');
                Route::PUT('complete', [App\Http\Controllers\Api\Student\AttendanceController::class, 'completeAllChapters'])->name('complete-all-chapters');
                Route::put('chapter/{chapter_id}/complete', [App\Http\Controllers\Api\Student\AttendanceController::class, 'completeAllLessons'])->name('complete-all-lessons');
            });
        });

        // 受講生-レッスン受講
        Route::patch('lesson_attendance/{lesson_attendance_id}', [App\Http\Controllers\Api\Student\LessonAttendanceController::class, 'patchStatus'])->name('lesson-attendance.patch-status');

        // 受講生-お知らせ
        Route::prefix('notification')->name('notification.')->group(function () {
            Route::get('index', [App\Http\Controllers\Api\Student\NotificationController::class, 'index'])->name('index');
            Route::post('mark-read', [App\Http\Controllers\Api\Student\NotificationController::class, 'markRead'])->name('mark-read');
            Route::get('{notification_id}', [App\Http\Controllers\Api\Student\NotificationController::class, 'show'])->name('show');
        });
    });

    // 講師側API
    Route::middleware('instructor')->group(function () {
        Route::prefix('instructor')->name('instructor.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'show'])->name('show');
            Route::post('update', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'update'])->name('update');

            // 講師-講座タグ一覧
            Route::get('tag/index', [App\Http\Controllers\Api\Instructor\TagController::class, 'index'])->name('tag.index');

            // 講師-講座
            Route::prefix('course')->group(function () {
                Route::name('course.')->group(function () {
                    Route::get('index', [App\Http\Controllers\Api\Instructor\CourseController::class, 'index'])->name('index');
                    Route::post('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'store'])->name('store');
                    Route::put('status', [App\Http\Controllers\Api\Instructor\CourseController::class, 'putStatus'])->name('put-status');
                    Route::put('capacity', [App\Http\Controllers\Api\Instructor\CourseController::class, 'putCapacity'])->name('put-capacity');
                    Route::delete('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'bulkDelete'])->name('bulk-delete');
                    Route::get('tag/index', [App\Http\Controllers\Api\Instructor\Course\TagController::class, 'index'])->name('tag.index');
                    Route::patch('deadline', [App\Http\Controllers\Api\Instructor\CourseDeadlineController::class, 'bulkUpdate'])->name('deadline.bulk-update');
                    Route::patch('capacity/clear', [App\Http\Controllers\Api\Instructor\CourseController::class, 'clearCapacity'])->name('capacity.clear');
                    Route::patch('capacity/clear-all', [App\Http\Controllers\Api\Instructor\CourseController::class, 'clearAllCapacity'])->name('capacity.clear-all');
                });

                Route::prefix('{course_id}')->group(function () {
                    Route::name('course.')->group(function () {
                        Route::get('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'show'])->name('show');
                        Route::post('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'update'])->name('update');
                        Route::delete('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'delete'])->name('delete');
                    });

                    // 講師-講座-チャプター
                    Route::prefix('chapter')->group(function () {
                        Route::name('chapter.')->group(function () {
                            Route::post('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'store'])->name('store');
                            Route::post('sort', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'sort'])->name('sort');
                            Route::put('status', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'putStatus'])->name('put-status');
                            Route::patch('status', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'patchStatus'])->name('patch-status');
                            Route::delete('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'bulkDelete'])->name('bulk-delete');
                            Route::delete('all', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'deleteAll'])->name('delete-all');
                        });

                        Route::prefix('{chapter_id}')->group(function () {
                            Route::name('chapter.')->group(function () {
                                Route::get('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'show'])->name('show');
                                Route::put('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'put'])->name('put');
                            });

                            // 講師-講座-チャプター-レッスン
                            Route::prefix('lesson')->group(function () {
                                Route::name('lesson.')->group(function () {
                                    Route::post('/', [App\Http\Controllers\Api\Instructor\LessonController::class, 'store'])->name('store');
                                    Route::post('sort', [App\Http\Controllers\Api\Instructor\LessonController::class, 'sort'])->name('sort');
                                    Route::put('status', [App\Http\Controllers\Api\Instructor\LessonController::class, 'putStatus'])->name('put-status');
                                    Route::delete('/', [App\Http\Controllers\Api\Instructor\LessonController::class, 'bulkDelete'])->name('bulk-delete');
                                    Route::delete('all', [App\Http\Controllers\Api\Instructor\LessonController::class, 'deleteAll'])->name('delete-all');
                                });

                                Route::prefix('{lesson_id}')->name('lesson.')->group(function () {
                                    Route::put('/', [App\Http\Controllers\Api\Instructor\LessonController::class, 'put'])->name('put');
                                    Route::delete('/', [App\Http\Controllers\Api\Instructor\LessonController::class, 'delete'])->name('delete');
                                    Route::patch('status', [App\Http\Controllers\Api\Instructor\LessonController::class, 'updateStatus'])->name('update-status');
                                    Route::patch('title', [App\Http\Controllers\Api\Instructor\LessonController::class, 'updateTitle'])->name('update-title');
                                });
                            });
                        });
                    });

                    // 講師-講座-お知らせ
                    Route::prefix('notification')->name('course.notification.')->group(function () {
                        Route::post('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'store'])->name('store');
                    });

                    // 講師-講座-受講
                    Route::prefix('attendance')->name('course.attendance.')->group(function () {
                        Route::get('follow-up', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'followUp'])->name('follow-up');
                        Route::get('expiring', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'expiring'])->name('expiring');
                        Route::prefix('status')->group(function () {
                            Route::get('{period}', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'showStatus'])->name('show-status');
                        });
                        Route::get('stuck-points', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'stuckPoints'])->name('stuck-points');
                        Route::get('{period}', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'loginRate'])->name('login-rate');
                    });
                });
            });

            // 講師-タグ
            Route::prefix('tag')->name('tag.')->group(function () {
                Route::post('/', [App\Http\Controllers\Api\Instructor\TagController::class, 'store'])->name('store');
                Route::prefix('{tag_id}')->group(function () {
                    Route::get('/', [App\Http\Controllers\Api\Instructor\TagController::class, 'show'])->name('show');
                    Route::put('/', [App\Http\Controllers\Api\Instructor\TagController::class, 'put'])->name('put');
                    Route::delete('/', [App\Http\Controllers\Api\Instructor\TagController::class, 'delete'])->name('delete');
                });
            });

            // 講師-受講
            Route::prefix('attendance')->name('attendance.')->group(function () {
                Route::post('/', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'store'])->name('store');
                // 講師-生徒学習状況
                Route::prefix('{attendance_id}')->group(function () {
                    Route::get('/', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'show'])->name('show');
                    Route::get('status', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'status'])->name('status');
                    Route::delete('/', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'delete'])->name('delete');
                });
            });

            // 講師-生徒
            Route::prefix('student')->name('student.')->group(function () {
                Route::get('index', [App\Http\Controllers\Api\Instructor\StudentController::class, 'index'])->name('index');
                Route::get('{student_id}', [App\Http\Controllers\Api\Instructor\StudentController::class, 'show'])->name('show');
                Route::post('/', [App\Http\Controllers\Api\Instructor\StudentController::class, 'store'])->name('store');
            });

            // 講師-お知らせ
            Route::prefix('notification')->name('notification.')->group(function () {
                Route::get('index', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'index'])->name('index');
                Route::prefix('type')->group(function () {
                    Route::put('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'updateType'])->name('update-type');
                    Route::put('all', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'updateTypeAll'])->name('update-type-all');
                });
                Route::prefix('status')->group(function () {
                    Route::put('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'putStatus'])->name('put-status');
                    Route::put('all', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'putStatusAll'])->name('put-status-all');
                });
                Route::delete('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'bulkDelete'])->name('bulk-delete');
                Route::prefix('{notification_id}')->group(function () {
                    Route::get('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'show'])->name('show');
                    Route::put('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'put'])->name('put');
                    Route::delete('/', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'delete'])->name('delete');
                });
            });
        });

        // マネージャーAPI
        Route::middleware('manager')->group(function () {
            Route::prefix('manager')->name('manager.')->group(function () {
                // マネージャー-タグ
                Route::prefix('tag')->name('tag.')->group(function () {
                    Route::prefix('{tag_id}')->group(function () {
                        Route::get('/', [App\Http\Controllers\Api\Manager\TagController::class, 'show'])->name('show');
                    });
                });
                // マネージャー-講師
                Route::prefix('instructor')->name('instructor.')->group(function () {
                    Route::post('/', [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'store'])->name('store');
                    Route::get('index', [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'index'])->name('index');
                    Route::prefix('{instructor_id}')->group(function () {
                        Route::get('/', [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'show'])->name('show');
                        Route::post('/', [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'update'])->name('update');
                        Route::get('total-current-attendance-count',
                            [App\Http\Controllers\Api\Manager\Instructor\InstructorController::class, 'totalCurrentAttendanceCount'])->name('total-current-attendance-count');
                        Route::prefix('course')->name('course.')->group(function () {
                            Route::get('index', [App\Http\Controllers\Api\Manager\Instructor\CourseController::class, 'index'])->name('index');
                        });
                    });
                });
                // マネージャー-講座
                Route::prefix('course')->group(function () {
                    Route::name('course.')->group(function () {
                        Route::get('index', [App\Http\Controllers\Api\Manager\CourseController::class, 'index'])->name('index');
                        Route::put('status', [App\Http\Controllers\Api\Manager\CourseController::class, 'putStatus'])->name('put-status');
                        Route::post('/', [App\Http\Controllers\Api\Manager\CourseController::class, 'store'])->name('store');
                        Route::post('deadline/clear-selected', [App\Http\Controllers\Api\Manager\CourseDeadlineController::class, 'clearSelected'])->name('deadline.clear-selected');
                    });
                    Route::prefix('{course_id}')->group(function () {
                        Route::name('course.')->group(function () {
                            Route::get('/', [App\Http\Controllers\Api\Manager\CourseController::class, 'show'])->name('show');
                            Route::post('/', [App\Http\Controllers\Api\Manager\CourseController::class, 'update'])->name('update');
                        });

                        // マネージャー-講座-チャプター
                        Route::prefix('chapter/{chapter_id}')->group(function () {
                            Route::name('chapter.')->group(function () {
                                Route::delete('/', [App\Http\Controllers\Api\Manager\ChapterController::class, 'delete'])->name('delete');
                                Route::patch('status', [App\Http\Controllers\Api\Manager\ChapterController::class, 'updateStatus'])->name('update-status');
                            });

                            // マネージャー-講座-チャプター-レッスン
                            Route::prefix('lesson')->name('lesson.')->group(function () {
                                Route::delete('/', [App\Http\Controllers\Api\Manager\LessonController::class, 'bulkDelete'])->name('bulk-delete');
                            });
                        });

                        // マネージャー生徒学習状況
                        Route::prefix('attendance')->name('course.attendance.')->group(function () {
                            Route::prefix('status')->group(function () {
                                Route::get('{period}', [App\Http\Controllers\Api\Manager\AttendanceController::class, 'showStatus'])->name('show-status');
                            });
                            Route::get('{period}', [App\Http\Controllers\Api\Manager\AttendanceController::class, 'loginRate'])->name('login-rate');
                        });
                    });

                    // マネージャー講師-タグ
                    Route::prefix('tag')->name('course.tag.')->group(function () {
                        Route::get('index', [App\Http\Controllers\Api\Manager\TagController::class, 'index'])->name('index');
                    });
                });
                // マネージャー-生徒
                Route::prefix('student')->name('student.')->group(function () {
                    Route::get('index', [App\Http\Controllers\Api\Manager\StudentController::class, 'index'])->name('index');
                });
                // マネージャー-お知らせ
                Route::prefix('notification')->name('notification.')->group(function () {
                    Route::get('index', [App\Http\Controllers\Api\Manager\NotificationController::class, 'index'])->name('index');
                    Route::prefix('type')->group(function () {
                        Route::put('all', [App\Http\Controllers\Api\Manager\NotificationController::class, 'updateTypeAll'])->name('update-type-all');
                    });
                    Route::put('status/all', [App\Http\Controllers\Api\Manager\NotificationController::class, 'putStatusAll'])->name('put-status-all');
                    Route::prefix('{notification_id}')->group(function () {
                        Route::put('/', [App\Http\Controllers\Api\Manager\NotificationController::class, 'put'])->name('put');
                        Route::delete('/', [App\Http\Controllers\Api\Manager\NotificationController::class, 'delete'])->name('delete');
                    });
                });
            });
        });
    });
});

Route::prefix('v1')->group(function () {
    Route::prefix('student')->name('student.')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\Student\StudentController::class, 'store'])->name('store');
        Route::post('verification/{token}', [App\Http\Controllers\Api\Student\StudentController::class, 'verifyCode'])->name('verify-code');
    });
    Route::prefix('instructor')->name('instructor.')->group(function () {
        Route::post('/', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'store'])->name('register');
        Route::post('verification/{token}', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'verifyCode'])->name('verify-code');
    });
});
