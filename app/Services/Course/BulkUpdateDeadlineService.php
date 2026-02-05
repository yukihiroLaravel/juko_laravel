<?php

namespace App\Services\Course;

use App\Model\CourseDeadline;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BulkUpdateDeadlineService
{
    public function execute(Collection $courses, array $deadlineParams): int
    {
        return DB::transaction(function () use ($courses, $deadlineParams) {

            $updatedCount = 0;

            foreach ($courses as $course) {

                $data = [
                    'fixed_date' => null,
                    'relative_days' => null,
                ];

                if ($deadlineParams['deadline_type'] === 'fixed') {
                    $data['fixed_date'] = $deadlineParams['fixed_date'];
                }

                if ($deadlineParams['deadline_type'] === 'relative') {
                    $data['relative_days'] = $deadlineParams['relative_days'];
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
