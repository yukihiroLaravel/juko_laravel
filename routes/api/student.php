<?php

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
    Route::get('/', [App\Http\Controllers\Api\Student\StudentController::class, 'show'])->name('show');
    Route::post('update', [App\Http\Controllers\Api\Student\StudentController::class, 'update'])->name('update');
    Route::get('learning-history', [App\Http\Controllers\Api\Student\LearningHistoryController::class, 'index'])->name('learning-history.index');
    Route::get('login-streak', [App\Http\Controllers\Api\Student\LoginController::class, 'loginStreak'])->name('login-streak');
});

// 受講生-受講
Route::prefix('attendances')->name('attendances.')->group(function () {
    Route::get('index', [App\Http\Controllers\Api\Student\AttendanceController::class, 'index'])->name('index');
    Route::prefix('{attendance_id}')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Student\AttendanceController::class, 'show'])->name('show');
        Route::get('progress', [App\Http\Controllers\Api\Student\AttendanceController::class, 'progress'])->name('progress');
        Route::PUT('complete', [App\Http\Controllers\Api\Student\AttendanceController::class, 'completeAllChapters'])->name('complete-all-chapters');
        Route::put('chapters/{chapter_id}/complete', [App\Http\Controllers\Api\Student\AttendanceController::class, 'completeAllLessons'])->name('complete-all-lessons');
        Route::get('stuck-points', [App\Http\Controllers\Api\Student\AttendanceController::class, 'stuckPoints'])->name('stuck-points');
    });
});

// 受講生-レッスン受講
Route::patch('lesson-attendances/{lesson_attendance_id}', [App\Http\Controllers\Api\Student\LessonAttendanceController::class, 'patchStatus'])->name('lesson-attendances.patch-status');

// 受講生-お知らせ
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('index', [App\Http\Controllers\Api\Student\NotificationController::class, 'index'])->name('index');
    Route::post('mark-read', [App\Http\Controllers\Api\Student\NotificationController::class, 'markRead'])->name('mark-read');
    Route::get('{notification_id}', [App\Http\Controllers\Api\Student\NotificationController::class, 'show'])->name('show');
});
