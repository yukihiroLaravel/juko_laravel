<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 認証不要API
|--------------------------------------------------------------------------
|
| v1 prefix 配下（auth:sanctum なし）。仮登録・認証コード検証など。
| prefix・name のネストは routes/api.php が担う。
|
*/

Route::prefix('students')->name('student.')->group(function () {
    Route::post('/', [App\Http\Controllers\Api\Student\StudentController::class, 'store'])->name('store');
    Route::post('verification/{token}', [App\Http\Controllers\Api\Student\StudentController::class, 'verifyCode'])->name('verify-code');
});

Route::prefix('instructors')->name('instructor.')->group(function () {
    Route::post('/', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'store'])->name('register');
    Route::post('verification/{token}', [App\Http\Controllers\Api\Instructor\InstructorController::class, 'verifyCode'])->name('verify-code');
});
