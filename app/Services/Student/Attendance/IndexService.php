<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\IndexDto;
use App\Model\Attendance;
use App\Model\Course;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class IndexService
{
    /**
     * @return Collection<Attendance>
     */
    public function __invoke(
        IndexDto $indexDto
    ): Collection {
        return Attendance::with('course.instructor')
            ->where('student_id', $indexDto->getStudentId())
            ->whereHas('course', function (Builder $query) use ($indexDto) {
                $query->when(! $indexDto->getSearchWord(), function ($query) {
                    $query->where('status', Course::STATUS_PUBLIC);
                })->when($indexDto->getSearchWord(), function ($query) use ($indexDto) {
                    $query->where('title', 'like', "%{$indexDto->getSearchWord()}%")
                        ->where('status', Course::STATUS_PUBLIC);
                });
            })->get();
    }
}
