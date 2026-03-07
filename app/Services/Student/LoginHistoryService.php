<?php

namespace App\Services\Student;

use App\Model\StudentLoginHistory;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Collection;

class LoginHistoryService
{
    public function __invoke(
        int $studentId,
        ?Carbon $startDate,
        ?Carbon $endDate
    ): Collection {

        $query = StudentLoginHistory::where('student_id', $studentId);

        if ($startDate) {
            $query->where('logged_in_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('logged_in_at', '<=', $endDate);
        }

        return $query
            ->orderByDesc('logged_in_at')
            ->get();
    }
}