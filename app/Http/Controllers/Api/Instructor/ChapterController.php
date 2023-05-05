<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Chapter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ChapterDeleteRequest;
use App\Http\Requests\Instructor\ChapterStoreRequest;
use App\Http\Resources\Instructor\ChapterStoreResponse;
use Illuminate\Support\Facades\Log;
use Exception;

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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ChapterStoreRequest $request)
    {
        try{
            // TODO 認証ユーザーが作成した講座かどうかを検証する必要がある

            $chapter = Chapter::create([
                'course_id' => $request->input('course_id'),
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

    public function delete(ChapterDeleteRequest $request)
    {
       $chapter = Chapter::findOrFail($request->chapter_id);
       $chapter->delete();
       return response()->json([
            "result" => true
        ]);
    }
}
