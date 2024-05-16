<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Notification;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Instructor\NotificationShowRequest;
use App\Http\Requests\Instructor\NotificationIndexRequest;
use App\Http\Requests\Instructor\NotificationStoreRequest;
use App\Http\Requests\Instructor\NotificationUpdateRequest;
use App\Http\Resources\Instructor\NotificationShowResource;
use App\Http\Resources\Instructor\NotificationIndexResource;

class NotificationController extends Controller
{
    /**
     * お知らせ一覧取得API
     *
     * @param NotificationIndexRequest $request
     * @return NotificationIndexResource
     */
    public function index(NotificationIndexRequest $request): NotificationIndexResource
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $notifications = Notification::with(['course'])
            ->where('instructor_id', Auth::guard('instructor')->user()->id)
            ->paginate($perPage, ['*'], 'page', $page);

        return new NotificationIndexResource($notifications);
    }

    /**
     * お知らせ詳細
     *
     * @param NotificationShowRequest $request
     * @return NotificationShowResource|JsonResponse
     */
    public function show(NotificationShowRequest $request)
    {
        $notification = Notification::with(['course'])
            ->findOrFail($request->notification_id);

        if ($notification->instructor_id !== Auth::guard('instructor')->user()->id) {
            return response()->json([
                'result' => false,
                'message' => 'Forbidden, not allowed to access this notification.',
            ], 403);
        }

        return new NotificationShowResource($notification);
    }

    /**
     * お知らせ登録
     *
     * @param NotificationStoreRequest $request
     * @return JsonResponse
     */
    public function store(NotificationStoreRequest $request): JsonResponse
    {
        Notification::create([
            'course_id'     => $request->course_id,
            'instructor_id' => Auth::guard('instructor')->user()->id,
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
     * @param NotificationUpdateRequest $request
     * @return JsonResponse
     */
    public function update(NotificationUpdateRequest $request): JsonResponse
    {
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
        ]);
    }

    public function delete(Request $request, $notification_id)
    {
        $instructor = Instructor::findOrFail($request->user()->id);
        $courseIds = Course::where('instructor_id', $instructor->id)->pluck('course_id')->toArray();

        $notification = Notification::with(['course'])
        ->findOrFail($request->notification_id);

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は更新を許可しない
            return response()->json([
                'result'  => false,
                'message' => 'Invalid chapter_id.',
            ], 403);

            $notification->delete();
            return response()->json([
                'result' => true,
                'message' => 'Notification deleted successfully.'
            ], 200);
        //↑をnotification_idがinstructore_idと一致しない場合は更新を許可しない。
        //もしかしたら、course_idも全て一致しないと更新を許可しない風にする？？
        //notification_idの中のインストラクターidと今ログインしているインストラクターidが同じか
        //そのためにはまず、ログインしているインストラクターidを持ってくる
    }
}
