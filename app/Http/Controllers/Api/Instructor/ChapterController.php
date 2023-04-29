<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;

class ChapterController extends Controller
{
    /**
     * チャプター名前変更API
     *
     * @param $chapter_id
     * @return array
     */
    public function update($chapter_id)
    {
        return response()->json([
            "result" => true,
        ]);

    }
}