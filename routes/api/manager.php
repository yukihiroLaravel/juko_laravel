<?php

use App\Http\Controllers\Api\Manager\AttendanceController;
use App\Http\Controllers\Api\Manager\ChapterController;
use App\Http\Controllers\Api\Manager\CourseController;
use App\Http\Controllers\Api\Manager\CourseDeadlineController;
use App\Http\Controllers\Api\Manager\Instructor\InstructorController;
use App\Http\Controllers\Api\Manager\NotificationController;
use App\Http\Controllers\Api\Manager\StudentController;
use App\Http\Controllers\Api\Manager\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| マネージャーAPI
|--------------------------------------------------------------------------
|
| auth:sanctum + v1 + instructor + manager ミドルウェア / manager. 名前空間配下。
| （マネージャーは講師認証の内側にネストする）
| ミドルウェア・prefix・name のネストは routes/api.php が担う。
|
*/

// マネージャー-タグ
Route::prefix('tags')->name('tags.')->group(function () {
    Route::prefix('{tag_id}')->group(function () {
        Route::get('/', [TagController::class, 'show'])->name('show');
    });
});

// マネージャー-講師
Route::prefix('instructors')->name('instructors.')->group(function () {
    Route::post('/', [InstructorController::class, 'store'])->name('store');
    Route::get('index', [InstructorController::class, 'index'])->name('index');
    Route::prefix('{instructor_id}')->group(function () {
        Route::get('/', [InstructorController::class, 'show'])->name('show');
        Route::post('/', [InstructorController::class, 'update'])->name('update');
        Route::get('total-current-attendance-count',
            [InstructorController::class, 'totalCurrentAttendanceCount'])->name('total-current-attendance-count');
        Route::prefix('courses')->name('courses.')->group(function () {
            Route::get('index', [App\Http\Controllers\Api\Manager\Instructor\CourseController::class, 'index'])->name('index');
        });
    });
});

// マネージャー-講座
Route::prefix('courses')->group(function () {
    Route::name('courses.')->group(function () {
        Route::get('index', [CourseController::class, 'index'])->name('index');
        Route::put('status', [CourseController::class, 'putStatus'])->name('put-status');
        Route::post('/', [CourseController::class, 'store'])->name('store');
        Route::post('deadline/clear-selected', [CourseDeadlineController::class, 'clearSelected'])->name('deadline.clear-selected');
    });
    Route::prefix('{course_id}')->group(function () {
        Route::name('courses.')->group(function () {
            Route::get('/', [CourseController::class, 'show'])->name('show');
            Route::post('/', [CourseController::class, 'update'])->name('update');
        });

        // マネージャー生徒学習状況
        Route::prefix('attendances')->name('courses.attendances.')->group(function () {
            Route::prefix('status')->group(function () {
                Route::get('{period}', [AttendanceController::class, 'showStatus'])->name('show-status');
            });
            Route::get('{period}', [AttendanceController::class, 'loginRate'])->name('login-rate');
        });
    });

    // マネージャー講師-タグ
    Route::prefix('tags')->name('courses.tags.')->group(function () {
        Route::get('index', [TagController::class, 'index'])->name('index');
    });
});

// マネージャー-チャプター
Route::prefix('chapters/{chapter_id}')->name('chapters.')->group(function () {
    Route::delete('/', [ChapterController::class, 'delete'])->name('delete');
    Route::patch('status', [ChapterController::class, 'updateStatus'])->name('update-status');
});

// マネージャー-生徒
Route::prefix('students')->name('students.')->group(function () {
    Route::get('index', [StudentController::class, 'index'])->name('index');
});

// マネージャー-お知らせ
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('index', [NotificationController::class, 'index'])->name('index');
    Route::prefix('type')->group(function () {
        Route::put('all', [NotificationController::class, 'updateTypeAll'])->name('update-type-all');
    });
    Route::put('status/all', [NotificationController::class, 'putStatusAll'])->name('put-status-all');
    Route::prefix('{notification_id}')->group(function () {
        Route::put('/', [NotificationController::class, 'put'])->name('put');
        Route::delete('/', [NotificationController::class, 'delete'])->name('delete');
    });
});
