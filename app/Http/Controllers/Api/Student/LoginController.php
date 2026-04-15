<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Model\Student;
use App\Services\Student\LoginStreakService;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    /**
     * 連続ログイン日数取得API
     */
    public function loginStreak(LoginStreakService $service): JsonResponse
    {
        /** @var Student $student */
        $student = auth()->user();

        return response()->json([
            'data' => [
                'login_streak_days' => $service($student),
            ],
        ]);
    }
}
