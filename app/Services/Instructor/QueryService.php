<?php

namespace App\Services\Instructor;

use App\Model\Instructor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
