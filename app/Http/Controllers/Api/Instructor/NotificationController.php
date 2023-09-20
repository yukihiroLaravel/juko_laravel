<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\NotificationIndexRequest;
use App\Http\Resources\Instructor\NotificationIndexResource;
use App\Http\Requests\Instructor\NotificationStoreRequest;
use App\Model\Notification;
use Illuminate\Support\Facades\Auth;

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
}
