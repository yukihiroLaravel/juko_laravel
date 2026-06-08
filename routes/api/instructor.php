<?php

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

Route::get('/', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'show'])->name('show');
Route::post('update', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'update'])->name('update');

// 講師-講座タグ一覧
Route::get('tags/index', [App\Http\Controllers\Api\Instructor\TagController::class, 'index'])->name('tags.index');

// 講師-講座
Route::prefix('courses')->group(function () {
    Route::name('courses.')->group(function () {
        Route::get('index', [App\Http\Controllers\Api\Instructor\CourseController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'store'])->name('store');
        Route::put('status', [App\Http\Controllers\Api\Instructor\CourseController::class, 'putStatus'])->name('put-status');
        Route::put('capacity', [App\Http\Controllers\Api\Instructor\CourseController::class, 'putCapacity'])->name('put-capacity');
        Route::delete('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'bulkDelete'])->name('bulk-delete');
        Route::get('tags/index', [App\Http\Controllers\Api\Instructor\Course\TagController::class, 'index'])->name('tags.index');
        Route::patch('deadline', [App\Http\Controllers\Api\Instructor\CourseDeadlineController::class, 'bulkUpdate'])->name('deadline.bulk-update');
        Route::patch('capacity/clear', [App\Http\Controllers\Api\Instructor\CourseController::class, 'clearCapacity'])->name('capacity.clear');
        Route::patch('capacity/clear-all', [App\Http\Controllers\Api\Instructor\CourseController::class, 'clearAllCapacity'])->name('capacity.clear-all');
    });

    // 講師-講座
    Route::prefix('{course_id}')->group(function () {
        Route::name('courses.')->group(function () {
            Route::get('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'show'])->name('show');
            Route::post('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'update'])->name('update');
            Route::delete('/', [App\Http\Controllers\Api\Instructor\CourseController::class, 'delete'])->name('delete');
        });

        // 講師-講座-チャプター
        Route::prefix('chapters')->name('chapters.')->group(function () {
            Route::post('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'store'])->name('store');
            Route::post('sort', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'sort'])->name('sort');
            Route::put('status', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'putStatus'])->name('put-status');
            Route::patch('status', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'patchStatus'])->name('patch-status');
            Route::delete('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'bulkDelete'])->name('bulk-delete');
            Route::delete('all', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'deleteAll'])->name('delete-all');
        });

        // 講師-講座-お知らせ
        Route::post('notifications', [App\Http\Controllers\Api\Instructor\NotificationController::class, 'store'])->name('courses.notifications.store');

        // 講師-講座-受講
        Route::prefix('attendances')->name('courses.attendances.')->group(function () {
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

// 講師-チャプター
Route::prefix('chapters')->name('chapters.')->group(function () {
    Route::prefix('{chapter_id}')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'show'])->name('show');
        Route::put('/', [App\Http\Controllers\Api\Instructor\ChapterController::class, 'put'])->name('put');

        // 講師-レッスン
        Route::prefix('lessons')->name('lessons.')->group(function () {
            Route::post('/', [App\Http\Controllers\Api\Instructor\LessonController::class, 'store'])->name('store');
            Route::post('sort', [App\Http\Controllers\Api\Instructor\LessonController::class, 'sort'])->name('sort');
            Route::put('status', [App\Http\Controllers\Api\Instructor\LessonController::class, 'putStatus'])->name('put-status');
            Route::delete('/', [App\Http\Controllers\Api\Instructor\LessonController::class, 'bulkDelete'])->name('bulk-delete');
            Route::delete('all', [App\Http\Controllers\Api\Instructor\LessonController::class, 'deleteAll'])->name('delete-all');
        });
    });
});

// 講師-レッスン
Route::prefix('lessons/{lesson_id}')->name('lessons.')->group(function () {
    Route::put('/', [App\Http\Controllers\Api\Instructor\LessonController::class, 'put'])->name('put');
    Route::delete('/', [App\Http\Controllers\Api\Instructor\LessonController::class, 'delete'])->name('delete');
    Route::patch('status', [App\Http\Controllers\Api\Instructor\LessonController::class, 'updateStatus'])->name('update-status');
    Route::patch('title', [App\Http\Controllers\Api\Instructor\LessonController::class, 'updateTitle'])->name('update-title');
});

// 講師-タグ
Route::prefix('tags')->name('tags.')->group(function () {
    Route::post('/', [App\Http\Controllers\Api\Instructor\TagController::class, 'store'])->name('store');
    Route::prefix('{tag_id}')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Instructor\TagController::class, 'show'])->name('show');
        Route::put('/', [App\Http\Controllers\Api\Instructor\TagController::class, 'put'])->name('put');
        Route::delete('/', [App\Http\Controllers\Api\Instructor\TagController::class, 'delete'])->name('delete');
    });
});

// 講師-受講
Route::prefix('attendances')->name('attendances.')->group(function () {
    Route::post('/', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'store'])->name('store');
    // 講師-生徒学習状況
    Route::prefix('{attendance_id}')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'show'])->name('show');
        Route::get('status', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'status'])->name('status');
        Route::delete('/', [App\Http\Controllers\Api\Instructor\AttendanceController::class, 'delete'])->name('delete');
    });
});

// 講師-生徒
Route::prefix('students')->name('students.')->group(function () {
    Route::get('index', [App\Http\Controllers\Api\Instructor\StudentController::class, 'index'])->name('index');
    Route::get('{student_id}', [App\Http\Controllers\Api\Instructor\StudentController::class, 'show'])->name('show');
    Route::post('/', [App\Http\Controllers\Api\Instructor\StudentController::class, 'store'])->name('store');
});

// 講師-お知らせ
Route::prefix('notifications')->name('notifications.')->group(function () {
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
