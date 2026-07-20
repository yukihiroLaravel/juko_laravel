<?php

use App\Http\Controllers\Api\Student\AttendanceController;
use App\Http\Controllers\Api\Student\LearningHistoryController;
use App\Http\Controllers\Api\Student\LessonAttendanceController;
use App\Http\Controllers\Api\Student\LoginController;
use App\Http\Controllers\Api\Student\NotificationController;
use App\Http\Controllers\Api\Student\StudentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 受講生API
|--------------------------------------------------------------------------
|
| auth:sanctum + v1 + student ミドルウェア / student. 名前空間配下。
| ミドルウェア・prefix・name のネストは routes/api.php が担う。
|
*/

// 受講生
Route::prefix('students')->group(function () {
    Route::get('/', [StudentController::class, 'show'])->name('show');
    Route::post('update', [StudentController::class, 'update'])->name('update');
    Route::get('learning-history', [LearningHistoryController::class, 'index'])->name('learning-history.index');
    Route::get('login-streak', [LoginController::class, 'loginStreak'])->name('login-streak');
});

// 受講生-受講
Route::prefix('attendances')->name('attendances.')->group(function () {
    Route::get('index', [AttendanceController::class, 'index'])->name('index');
    Route::prefix('{attendance_id}')->group(function () {
        Route::get('/', [AttendanceController::class, 'show'])->name('show');
        Route::get('progress', [AttendanceController::class, 'progress'])->name('progress');
        Route::put('complete', [AttendanceController::class, 'completeAllChapters'])->name('complete-all-chapters');
        Route::put('chapters/{chapter_id}/complete', [AttendanceController::class, 'completeAllLessons'])->name('complete-all-lessons');
        Route::get('stuck-points', [AttendanceController::class, 'stuckPoints'])->name('stuck-points');
    });
});

// 受講生-レッスン受講
Route::patch('lesson-attendances/{lesson_attendance_id}', [LessonAttendanceController::class, 'patchStatus'])->name('lesson-attendances.patch-status');

// 受講生-お知らせ
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('index', [NotificationController::class, 'index'])->name('index');
    Route::post('mark-read', [NotificationController::class, 'markRead'])->name('mark-read');
    Route::get('{notification_id}', [NotificationController::class, 'show'])->name('show');
});
