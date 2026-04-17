<?php

namespace App\Services\Student;

use App\Model\Student;
use Carbon\CarbonImmutable;

class LoginStreakService
{
    /**
     * 連続ログイン日数を取得
     */
    public function __invoke(Student $student): ?int
    {
        $loginDates = $student->loginHistories()
            ->selectRaw('DATE(logged_in_at) as login_date')
            ->groupBy('login_date')
            ->orderByDesc('login_date')
            ->pluck('login_date')
            ->map(fn (string $date) => CarbonImmutable::parse($date));

        if ($loginDates->isEmpty()) {
            return null;
        }

        $today = CarbonImmutable::today();

        // 今日ログインしていない場合
        if (! $loginDates[0]->isSameDay($today)) {
            return null;
        }

        return $loginDates
            ->values()
            ->takeWhile(fn (CarbonImmutable $date, int $i) => $date->isSameDay($today->subDays($i)))
            ->count();
    }
}
