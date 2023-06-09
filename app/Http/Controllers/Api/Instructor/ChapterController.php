<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Chapter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ChapterDeleteRequest;
use App\Http\Requests\Instructor\ChapterStoreRequest;
use App\Http\Requests\Instructor\ChapterPatchRequest;
use App\Http\Requests\Instructor\ChapterShowRequest;
use App\Http\Resources\Instructor\ChapterStoreResource;
use App\Http\Resources\Instructor\ChapterPatchResource;
use Illuminate\Support\Facades\Log;
use Exception;

class ChapterController extends Controller
{
    /**
     * チャプター新規作成API
     *
     * @param ChapterStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ChapterStoreRequest $request)
    {
        try {
            $chapter = Chapter::create([
                'course_id' => $request->input('course_id'),
                'title' => $request->input('title'),
            ]);

            return response()->json([
                'result' => true,
                'data' => new ChapterStoreResource($chapter),
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'result' => false
            ], 500);
        }
    }

    /**
     * チャプター詳細情報を取得
     *
     * @param ChapterGetRequest $request
     * @return ChapterShowResource
     */

    public function show(ChapterShowRequest $request, $chapter_id)
    {
        $chapter = Chapter::with('lessons')
            ->findOrFail($chapter_id);
        
        $lessons = [];
        foreach ($chapter->lessons as $lesson){
            $lessons[] = [
                'lesson_id' => $lesson->id,
                'title' => $lesson->title,
                'url' => $lesson->url,
                'remark' => $lesson-> remarks,
                'order' => $lesson->order,
            ];
        }
        $data = [
            'chapter_id' => $chapter->id,
            'title' => $chapter->title,
            'lessons' => $lessons,
        ];

        return response()->json([
            'data' => $data,
        ]); 
    }
    /**
     * チャプター更新API
     *
     * @param ChapterPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ChapterPatchRequest $request)
    {
        $chapter = Chapter::findOrFail($request->chapter_id);
        $chapter->update([
            'title' => $request->title
        ]);

        return response()->json([
            'result' => true,
            'data' => new ChapterPatchResource($chapter),
        ]);
    }

    /**
     * チャプター削除API
     *
     * @param ChapterDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(ChapterDeleteRequest $request)
    {
        $chapter = Chapter::findOrFail($request->chapter_id);
        $chapter->delete();
        return response()->json([
            "result" => true
        ]);
    }

    /**
     * チャプター並び替えAPI
     */
    public function sort()
    {
        return response()->json([]);
    }
}
