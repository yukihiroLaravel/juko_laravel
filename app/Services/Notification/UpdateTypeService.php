<?php

namespace App\Services\Notification;

use App\Model\Notification;
use Illuminate\Support\Collection;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UpdateTypeService
{
    /**
     * 通知タイプを一括更新
     *
     * @param  Collection<int, Notification>  $notifications
     * @param  array<int>  $allowedInstructorIds
     * @param  string  $type
     * @throws AuthorizationException
     */
    public function __invoke(Collection $notifications, array $allowedInstructorIds, string $type): void
    {

        // トランザクション内で一括更新
        DB::beginTransaction();
        try {
            Notification::whereIn('id', $notifications->pluck('id'))
                ->update(['type' => $type]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
