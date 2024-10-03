<?php

namespace App\Services\Instructor;

use App\Model\Instructor;
use Illuminate\Database\Eloquent\Collection;

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
     * マネージャーとその配下の講師を取得する
     *
     * @param int $managerId
     * @return Instructor
     */
    public function getManagerWithSubordinates(int $managerId): Instructor
    {
        return Instructor::with('managings')->findOrFail($managerId);
    }

     /**
     * マネージャーとその配下の講師をIDリストで取得し、ソートおよびページネーションを適用
     *
     * @param array $instructorIds
     * @param string $sortBy
     * @param string $order
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInstructorsWithPagination(array $instructorIds, string $sortBy, string $order, int $perPage, int $page)
    {
        return Instructor::whereIn('id', $instructorIds)
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
