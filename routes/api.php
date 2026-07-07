<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| ロール単位でルート定義を routes/api/ 配下のファイルに分割している。
| このファイルはミドルウェア・prefix・name のネスト構造と、各ファイルの
| 読み込みのみを担う。各エンドポイントの定義は下記ファイルを参照:
|   - routes/api/student.php    受講生API
|   - routes/api/instructor.php 講師API
|   - routes/api/manager.php    マネージャーAPI（講師認証の内側）
|   - routes/api/guest.php      認証不要API（仮登録・コード検証）
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
    Route::middleware('student')->name('student.')
        ->group(base_path('routes/api/student.php'));

    // 講師側API
    Route::middleware('instructor')->group(function () {
        Route::prefix('instructor')->name('instructor.')
            ->group(base_path('routes/api/instructor.php'));

        // マネージャーAPI（講師認証の内側にネスト）
        Route::middleware('manager')->prefix('manager')->name('manager.')
            ->group(base_path('routes/api/manager.php'));
    });
});

// 認証不要API（仮登録・コード検証）
Route::prefix('v1')->group(base_path('routes/api/guest.php'));

