<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\IndexDto;
use App\Model\Course;
use App\Model\Tag;
use Illuminate\Support\Collection;

class TagIndexService
{
    public function __invoke(
        IndexDto $indexDto
    ): Collection {
        return Tag::with([
            'courses.attendances',
        ])
            ->whereHas('courses.attendances', function ($query) use ($indexDto) {
                $query->where('student_id', $indexDto->getStudentId());
            })
            ->whereHas('courses', function ($query) use ($indexDto) {
                $query->when(! $indexDto->getSearchWord(), function ($query) {
                    $query->where('status', Course::STATUS_PUBLIC);
                })->when($indexDto->getSearchWord(), function ($query) use ($indexDto) {
                    $query->where('title', 'like', "%{$indexDto->getSearchWord()}%")
                        ->where('status', Course::STATUS_PUBLIC);
                });
            })
            ->get();
    }
}
