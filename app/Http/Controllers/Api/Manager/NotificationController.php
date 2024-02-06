<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\NotificationIndexResource;
use App\Model\Notification;
use App\Model\Student;
use App\Model\Attendance;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * お知らせ一覧取得API
     *
     * @param Request $request
     * @return NotificationIndexResource
     */
    public function index(Request $request)
    {
        return response()->json([]);
    }
}
