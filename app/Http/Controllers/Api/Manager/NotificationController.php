<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\NotificationShowRequest;
use App\Http\Resources\Instructor\NotificationShowResource;
use App\Model\Notification;

class NotificationController extends Controller
{
    /**
     * お知らせ詳細
     *
     * @param NotificationShowRequest $request
     * @return NotificationShowResource
     */

    //テーブルから指定されたnotification_idのお知らせ詳細を取得
    public function show(NotificationShowRequest $request)
    {
        $notification = Notification::findOrFail($request->notification_id);

        return new NotificationShowResource($notification);
    }
}
