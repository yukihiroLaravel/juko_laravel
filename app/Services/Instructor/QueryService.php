<?php

namespace App\Services\Instructor;

use App\Model\Instructor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class QueryService
{
    /**
     * 選択された講師を取得
     *
     * @param int $InstructorId
     * @return Instructor
     */
    public function getInstructor(int $InstructorId): Instructor
    {
        return Instructor::findOrFail($InstructorId);
    }

    /**
     * 講師とその配下の講師を取得
     *
     * @param int $managerId
     * @return Instructor
     */
    public function getManagerWithManagings(int $managerId): Instructor
    {
        return Instructor::with('managings')->findOrFail($managerId);
    }

    /**
     * 講師とその配下の講師を取得(ページネーション)
     *
     * @param array $instructorIds
     * @param string $sortBy
     * @param string $order
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator
     */
    public function getInstructorsWithPagination(array $instructorIds, string $sortBy, string $order, int $perPage, int $page): LengthAwarePaginator
    {
        return Instructor::whereIn('id', $instructorIds)
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
