<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ChapterStoreRequest;
use App\Http\Resources\Instructor\ChapterStoreResponse;
use App\Model\Chapter;
use App\Http\Requests\Instructor\ChapterGetRequest;
use App\Model\Course;
use Exception;
use Illuminate\Support\Facades\Log;

class ChapterController extends Controller
{
    public function sort()
    {
        return response()->json([]);
    }

    /**
     * チャプター新規作成
     *
     * @param ChapterStoreRequest $request
     * @param int $course_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ChapterStoreRequest $request, $course_id)
    {
        try{
            $course = Course::findOrFail($course_id);

            // TODO 認証ユーザーが作成した講座かどうかを検証する必要がある

            $chapter = Chapter::create([
                'course_id' => $course_id,
                'title' => $request->input('title'),
            ]);

            return response()->json([
                'result' => true,
                'data' => new ChapterStoreResponse($chapter),
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'result' => false
            ], 500);
        }
    }
}
