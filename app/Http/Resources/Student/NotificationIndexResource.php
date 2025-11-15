<?php

namespace App\Http\Resources\Student;

use App\Model\Notification;
use App\Http\Resources\Base\Student\NotificationResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationIndexResource extends JsonResource
{
    /** @var LengthAwarePaginator */
    public $resource;

    #[\Override]
    public function toArray($request)
    {
        /** @var LengthAwarePaginator $notifications */
        $notifications = $this->resource;

        return [
            'notifications' => $notifications->getCollection()
                ->map(function (Notification $notification) use ($request) {
                    // Service側で積んだ擬似リレーションを使う
                    $attendance = $notification->getRelation('student_attendance');
                    $deadline   = $attendance?->attendance_deadline; // DATE型（cast）

                    // 既存ベースに追記
                    return (new NotificationResource($notification))->toArray($request) + [
                        'attendance_deadline' => $deadline?->toDateString(), // 'Y-m-d' or null
                    ];
                })
                ->values()
                ->toArray(),

            // totalはServiceでフィルタ後件数になっているので整合性◎
            'pagination' => [
                'page'  => $notifications->currentPage(),
                'total' => $notifications->total(),
            ],
        ];
    }
}
