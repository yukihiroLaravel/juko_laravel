<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ChapterGetRequest;
use App\Model\Chapter;

class ChapterController extends Controller
{
    /**
     * チャプター新規作成
     *
     * @param ChapterGetRequest $request
     * @param int $course_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ChapterGetRequest $request, $course_id)
    {
        $chapter = Chapter::create([
            'course_id' => $course_id,
            'title' => $request->input('title'),
        ]);

        return response()->json($chapter);
    }
}
