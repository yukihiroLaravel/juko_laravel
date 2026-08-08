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
        IndexDto $indexDto, int $perPage, int $page, ?int $tagId
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
                $searchWord = $indexDto->getSearchWord();
                $query->where('status', CourseStatusEnum::PUBLIC->value)
                    ->when(filled($searchWord), function (Builder $query) use ($searchWord) {
                        $query->where(function (Builder $query) use ($searchWord) {
                            $query->where('title', 'like', "%{$searchWord}%")
                                ->orWhereHas('tags', fn (Builder $query) => $query->where('content', 'like', "%{$searchWord}%"));
                        });
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
