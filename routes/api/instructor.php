<?php

use App\Http\Controllers\Api\Instructor\AttendanceController;
use App\Http\Controllers\Api\Instructor\ChapterController;
use App\Http\Controllers\Api\Instructor\CourseController;
use App\Http\Controllers\Api\Instructor\CourseDeadlineController;
use App\Http\Controllers\Api\Instructor\InstructorController;
use App\Http\Controllers\Api\Instructor\LessonController;
use App\Http\Controllers\Api\Instructor\NotificationController;
use App\Http\Controllers\Api\Instructor\StudentController;
use App\Http\Controllers\Api\Instructor\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 講師API
|--------------------------------------------------------------------------
|
| auth:sanctum + v1 + instructor ミドルウェア / instructor. 名前空間配下。
| ミドルウェア・prefix・name のネストは routes/api.php が担う。
|
*/

Route::get('/', [InstructorController::class, 'show'])->name('show');
Route::post('update', [InstructorController::class, 'update'])->name('update');

// 講師-講座タグ一覧
Route::get('tags/index', [TagController::class, 'index'])->name('tags.index');

// 講師-講座
Route::prefix('courses')->group(function () {
    Route::name('courses.')->group(function () {
        Route::get('index', [CourseController::class, 'index'])->name('index');
        Route::post('/', [CourseController::class, 'store'])->name('store');
        Route::put('status', [CourseController::class, 'putStatus'])->name('put-status');
        Route::put('capacity', [CourseController::class, 'putCapacity'])->name('put-capacity');
        Route::delete('/', [CourseController::class, 'bulkDelete'])->name('bulk-delete');
        Route::get('tags/index', [App\Http\Controllers\Api\Instructor\Course\TagController::class, 'index'])->name('tags.index');
        Route::patch('deadline', [CourseDeadlineController::class, 'bulkUpdate'])->name('deadline.bulk-update');
        Route::patch('capacity/clear', [CourseController::class, 'clearCapacity'])->name('capacity.clear');
        Route::patch('capacity/clear-all', [CourseController::class, 'clearAllCapacity'])->name('capacity.clear-all');
    });

    // 講師-講座
    Route::prefix('{course_id}')->group(function () {
        Route::name('courses.')->group(function () {
            Route::get('/', [CourseController::class, 'show'])->name('show');
            Route::post('/', [CourseController::class, 'update'])->name('update');
            Route::delete('/', [CourseController::class, 'delete'])->name('delete');
            Route::post('copy', [CourseController::class, 'copy'])->name('copy');  // 講座複製
        });

        // 講師-講座-チャプター
        Route::prefix('chapters')->name('chapters.')->group(function () {
            Route::post('/', [ChapterController::class, 'store'])->name('store');
            Route::post('sort', [ChapterController::class, 'sort'])->name('sort');
            Route::put('status', [ChapterController::class, 'putStatus'])->name('put-status');
            Route::patch('status', [ChapterController::class, 'patchStatus'])->name('patch-status');
            Route::delete('/', [ChapterController::class, 'bulkDelete'])->name('bulk-delete');
            Route::delete('all', [ChapterController::class, 'deleteAll'])->name('delete-all');
        });

        // 講師-講座-お知らせ
        Route::prefix('notifications')->name('courses.notifications.')->group(function () {
            Route::post('/', [NotificationController::class, 'store'])->name('store');
        });

        // 講師-講座-受講
        Route::prefix('attendances')->name('courses.attendances.')->group(function () {
            Route::get('follow-up', [AttendanceController::class, 'followUp'])->name('follow-up');
            Route::get('expiring', [AttendanceController::class, 'expiring'])->name('expiring');
            Route::prefix('status')->group(function () {
                Route::get('{period}', [AttendanceController::class, 'showStatus'])->name('show-status');
            });
            Route::get('stuck-points', [AttendanceController::class, 'stuckPoints'])->name('stuck-points');
            Route::get('{period}', [AttendanceController::class, 'loginRate'])->name('login-rate');
        });
    });
});

// 講師-チャプター
Route::prefix('chapters')->name('chapters.')->group(function () {
    Route::prefix('{chapter_id}')->group(function () {
        Route::get('/', [ChapterController::class, 'show'])->name('show');
        Route::put('/', [ChapterController::class, 'put'])->name('put');

        // 講師-レッスン
        Route::prefix('lessons')->name('lessons.')->group(function () {
            Route::post('/', [LessonController::class, 'store'])->name('store');
            Route::post('sort', [LessonController::class, 'sort'])->name('sort');
            Route::put('status', [LessonController::class, 'putStatus'])->name('put-status');
            Route::delete('/', [LessonController::class, 'bulkDelete'])->name('bulk-delete');
            Route::delete('all', [LessonController::class, 'deleteAll'])->name('delete-all');
        });
    });
});

// 講師-レッスン
Route::prefix('lessons/{lesson_id}')->name('lessons.')->group(function () {
    Route::put('/', [LessonController::class, 'put'])->name('put');
    Route::delete('/', [LessonController::class, 'delete'])->name('delete');
    Route::patch('status', [LessonController::class, 'updateStatus'])->name('update-status');
    Route::patch('title', [LessonController::class, 'updateTitle'])->name('update-title');
});

// 講師-タグ
Route::prefix('tags')->name('tags.')->group(function () {
    Route::post('/', [TagController::class, 'store'])->name('store');
    Route::prefix('{tag_id}')->group(function () {
        Route::get('/', [TagController::class, 'show'])->name('show');
        Route::put('/', [TagController::class, 'put'])->name('put');
        Route::delete('/', [TagController::class, 'delete'])->name('delete');
    });
});

// 講師-受講
Route::prefix('attendances')->name('attendances.')->group(function () {
    Route::post('/', [AttendanceController::class, 'store'])->name('store');
    // 講師-生徒学習状況
    Route::prefix('{attendance_id}')->group(function () {
        Route::get('/', [AttendanceController::class, 'show'])->name('show');
        Route::get('status', [AttendanceController::class, 'status'])->name('status');
        Route::delete('/', [AttendanceController::class, 'delete'])->name('delete');
    });
});

// 講師-生徒
Route::prefix('students')->name('students.')->group(function () {
    Route::get('index', [StudentController::class, 'index'])->name('index');
    Route::get('{student_id}', [StudentController::class, 'show'])->name('show');
    Route::post('/', [StudentController::class, 'store'])->name('store');
});

// 講師-お知らせ
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('index', [NotificationController::class, 'index'])->name('index');
    Route::prefix('type')->group(function () {
        Route::put('/', [NotificationController::class, 'updateType'])->name('update-type');
        Route::put('all', [NotificationController::class, 'updateTypeAll'])->name('update-type-all');
    });
    Route::prefix('status')->group(function () {
        Route::put('/', [NotificationController::class, 'putStatus'])->name('put-status');
        Route::put('all', [NotificationController::class, 'putStatusAll'])->name('put-status-all');
    });
    Route::delete('/', [NotificationController::class, 'bulkDelete'])->name('bulk-delete');
    Route::prefix('{notification_id}')->group(function () {
        Route::get('/', [NotificationController::class, 'show'])->name('show');
        Route::put('/', [NotificationController::class, 'put'])->name('put');
        Route::delete('/', [NotificationController::class, 'delete'])->name('delete');
    });
});
