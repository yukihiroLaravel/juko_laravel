<?php

namespace App\Services\Course;

use App\Model\Course;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ClearCapacityService
{
    /**
     * @param Collection<int, Course> $courses
     */
    public function __invoke(Collection $courses): int
    {
        // 受講が存在する講座がある場合はエラー
        $hasCourseWithAttendances = $courses->filter(
            fn (Course $course) => $course->attendances()->exists()
        )->isNotEmpty();

        if ($hasCourseWithAttendances) {
            throw ValidationException::withMessages([
                'courses' => '受講者が存在する講座が含まれているため、定員を削除できません。',
            ]);
        }

        return Course::whereIn('id', $courses->pluck('id'))->update(['capacity' => null]);
    }
}