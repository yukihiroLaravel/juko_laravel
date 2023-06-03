<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonStoreRequest;
use App\Http\Resources\Instructor\LessonStoreResource;
use App\Http\Requests\Instructor\LessonEditRequest;
use App\Model\Lesson;
use Exception;
use Illuminate\Support\Facades\Log;

class LessonController extends Controller
{
    /**
     * レッスン新規作成API
     *
     * @param  LessonStoreRequest  $request
     * @return LessonStoreResource
     */
    public function store(LessonStoreRequest $request)
    {
        try {
            $lesson = Lesson::create([
                'chapter_id' => $request->input('chapter_id'),
                'title' => $request->input('title'),
                'status' =>  Lesson::STATUS_PRIVATE,
            ]);

            return response()->json([
                "result" => true,
                "data" => new LessonStoreResource($lesson),
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);
        }
    }

    /**
     * レッスン並び替えAPI
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sort()
    {
        return response()->json([]);
    }

    /**
     * レッスン編集
     *
     * @param LessonEditRequest 
     * @return LessonEditResource
     */
    public function edit(LessonEditRequest $request, $lesson_id)
    {   
        $lesson = Lesson::findOrFail($lesson_id);
        // レッスンのデータを編集画面に適した形式で返す
        $data = [
            'chapter_id' => $lesson->chapter_id,
            'title' => 'チャプタータイトル',
            'lessons' => [
                [
                    'lesson_id' => $lesson->id,
                    'title' => $lesson->title,
                    'url' => $lesson->url,
                    'remarks' => $lesson->remarks,
                    'order' => $lesson->order,
                ],
            ],
        ];
        return response()->json([
            'data' => $data,
        ]);
    }
}