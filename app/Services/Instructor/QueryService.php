<?php

namespace App\Services\Instructor;

use App\Model\Instructor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class QueryService
{
    /**
     * 選択された講師を取得
     */
    public function getInstructor(int $InstructorId): Instructor
    {
        return Instructor::findOrFail($InstructorId);
    }

    /**
     * 講師とその配下の講師を取得
     */
    public function getManagerWithManagings(int $managerId): Instructor
    {
        return Instructor::with('managings')->findOrFail($managerId);
    }

    /**
     * 講師とその配下の講師を取得(ページネーション)
     */
    public function getPaginatedInstructors(array $instructorIds, string $sortBy, string $order, int $perPage, int $page): LengthAwarePaginator
    {
        return Instructor::whereIn('id', $instructorIds)
            ->withCount([
                'courses as student_count' => function ($query) {
                    $query->join('attendances', 'courses.id', '=', 'attendances.course_id')
                    ->select(DB::raw('COUNT(DISTINCT attendances.student_id)'));
                }
            ])
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
