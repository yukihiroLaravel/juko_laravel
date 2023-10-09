<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\NotificationDeleteRequest;
use App\Http\Requests\Instructor\NotificationIndexRequest;
use App\Http\Resources\Instructor\NotificationIndexResource;
use App\Http\Requests\Instructor\NotificationShowRequest;
use App\Http\Resources\Instructor\NotificationShowResource;
use App\Http\Requests\Instructor\NotificationStoreRequest;
use App\Http\Requests\Instructor\NotificationUpdateRequest;
use App\Http\Resources\Instructor\NotificationUpdateResource;
use App\Model\Notification;

class NotificationController extends Controller
{
    /**
     * 講師側お知らせ一覧取得API
     *
     * @param NotificationIndexRequest $request
     * @return NotificationIndexResource
     */
    public function index(NotificationIndexRequest $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $notifications = Notification::with(['course'])
                                        ->where('instructor_id', 1) //講師IDは仮で1を指定
                                        ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationIndexResource($notifications);
    }

    /**
     * お知らせ詳細
     *
     * @param NotificationShowRequest $request
     * @return NotificationShowResource
     */
    public function show(NotificationShowRequest $request)
    {
        $notification = Notification::findOrFail($request->notification_id);

        return new NotificationShowResource($notification);
    }

    /**
     * お知らせ登録
     *
     * @param NotificationStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(NotificationStoreRequest $request)
    {
        Notification::create([
            'course_id'     => $request->course_id,
            'instructor_id' => 1,
            'title'         => $request->title,
            'type'          => $request->type,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'content'       => $request->content,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * お知らせ更新API
     *
     * @param   NotificationUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(NotificationUpdateRequest $request) {
        $notification = Notification::findOrFail($request->notification_id);
        $notification->fill([
            'type'  => $request->type,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'title' => $request->title,
            'content' => $request->content,
        ])
        ->save();

        return response()->json([
            'result' => true,
            'data' => new NotificationUpdateResource($notification),
        ]);
    }

    /**
     * お知らせ通知削除API
     *
     * @param NotificationDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(NotificationDeleteRequest $request)
    {
        try {
            Notification::findOrFail($request->notification_id)
            ->delete();

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'result' => false,
                'message' => 'Notification not found',
            ], 500); // 500 エラーコードを返す
        }
    }
}
