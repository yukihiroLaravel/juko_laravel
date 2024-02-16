<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\NotificationIndexRequest;
use App\Http\Resources\Manager\NotificationIndexResource;
use App\Model\Notification;
use App\Model\Instructor;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * マネージャー側のお知らせ一覧取得API
     *
     * @param NotificationIndexRequest $request
     * @return NotificationIndexResource
     */
    public function index(NotificationIndexRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $notifications = Notification::with(['course'])
                                        ->whereIn('instructor_id', $instructorIds)
                                        ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationIndexResource($notifications);
    }
}
