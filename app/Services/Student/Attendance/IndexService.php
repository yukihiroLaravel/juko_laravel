<?php

namespace App\Services\Student\Attendance;

use App\Dto\Student\Attendance\IndexDto;
use App\Enums\Course\StatusEnum as CourseStatusEnum;
use App\Model\Attendance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexService
{
    /**
     * @return LengthAwarePaginator<Attendance>
     */
    public function __invoke(
        IndexDto $indexDto,
        int $perPage,
        int $page,
        ?int $tagId
    ): LengthAwarePaginator {
        // 受講情報を関連情報と一緒に取得
        return Attendance::with([
            'course.instructor',
            'course.publicChapters.publicLessons',
            'lessonAttendances',
            'course.tags',
            'course.courseDeadline',
        ])
            ->where('student_id', $indexDto->getStudentId())
            ->whereHas('course', function (Builder $query) use ($indexDto) {
                $query->when(! $indexDto->getSearchWord(), function (Builder $query) {
                    $query->where('status', CourseStatusEnum::PUBLIC->value);
                })->when($indexDto->getSearchWord(), function (Builder $query) use ($indexDto) {
                    $query->where('title', 'like', "%{$indexDto->getSearchWord()}%")
                        ->orWhereHas('tags', function (Builder $query) use ($indexDto) {
                            $query->where('content', 'like', "%{$indexDto->getSearchWord()}%");
                        })
                        ->where('status', CourseStatusEnum::PUBLIC->value);
                });
            })
            ->when($tagId, function (Builder $query) use ($tagId) {
                $query->whereHas('course.tags', function (Builder $query) use ($tagId) {
                    $query->where('tags.id', $tagId);
                });
            })
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
