<?php

namespace App\Services\Student;

use App\Model\StudentLoginHistory;
use Illuminate\Support\Carbon;

class LoginHistoryService
{
    public function getLoginHistories(
        int $studentId,
        Carbon $startDate,
        Carbon $endDate
    ): array {

        $histories = StudentLoginHistory::where('student_id', $studentId)
            ->whereBetween('logged_in_at', [$startDate, $endDate])
            ->orderByDesc('logged_in_at')
            ->get();

        return [
            'count' => $histories->count(),
            'histories' => $histories
        ];
    }
}