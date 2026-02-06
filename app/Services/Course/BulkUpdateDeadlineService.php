<?php

namespace App\Services\Course;

use App\Model\Course;
use App\Model\CourseDeadline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BulkUpdateDeadlineService
{
    /**
     * 講座の受講期限を一括更新する
     *
     * @param  Collection<int, Course>  $courses
     * @param  array{deadline_type: string, fixed_date?: string, relative_days?: int}  $deadlineParams
     */
    
    public function __invoke(Collection $courses, array $deadlineParams): int
    {
        return DB::transaction(function () use ($courses, $deadlineParams) {

            $updatedCount = 0;

            foreach ($courses as $course) {

                // none の場合は期限レコードを削除
                if ($deadlineParams['deadline_type'] === 'none') {
                    CourseDeadline::where('course_id', $course->id)->delete();
                    $updatedCount++;
                    continue;
                }

                $data = [
                    'fixed_date' => null,
                    'relative_days' => null,
                ];

                if ($deadlineParams['deadline_type'] === 'fixed') {
                    $data['fixed_date'] = $deadlineParams['fixed_date'] ?? null;
                }

                if ($deadlineParams['deadline_type'] === 'relative') {
                    $data['relative_days'] = $deadlineParams['relative_days'] ?? null;
                }

                CourseDeadline::updateOrCreate(
                    ['course_id' => $course->id],
                    $data
                );

                $updatedCount++;
            }

            return $updatedCount;
        });
    }
}