<?php

namespace App\Services\Course;

use App\Model\Attendance;
use App\Model\Course;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClearCapacityService
{
    /**
     * @param  Collection<int, Course>  $courses
     */
    public function __invoke(Collection $courses): int
    {
        return DB::transaction(function () use ($courses) {
            $courseIds = $courses->pluck('id');

            // N+1を避けるため1クエリでチェック
            $hasAttendances = Attendance::whereIn('course_id', $courseIds)->exists();

            if ($hasAttendances) {
                throw ValidationException::withMessages([
                    'courses' => '受講者が存在する講座が含まれているため、定員を削除できません。',
                ]);
            }

            return Course::whereIn('id', $courseIds)->update(['capacity' => null]);
        });
    }
}