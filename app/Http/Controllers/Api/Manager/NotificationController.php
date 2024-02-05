<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;

use App\Http\Requests\Instructor\NotificationShowRequest;
use App\Http\Resources\Instructor\NotificationShowResource;


class NotificationController extends Controller
{
    
    /**
     * お知らせ詳細
     *
     * @param NotificationShowRequest $request
     * @return NotificationShowResource
     */
    public function show()
    {
        return response()->json([]);    //空の配列を返すメソッド
    }

    
}
