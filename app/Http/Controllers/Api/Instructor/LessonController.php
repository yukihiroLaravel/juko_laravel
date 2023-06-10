<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonStoreRequest;
use App\Http\Resources\Instructor\LessonStoreResource;
use App\Http\Requests\Instructor\LessonUpdateRequest;
use App\Http\Resources\Instructor\LessonUpdateResource;
use Illuminate\Http\Request;
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
                'status' => 'private',
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

    public function update(LessonUpdateRequest $request)
    {
        Lesson::findOrFail($request->lesson_id)->update([
            'title' => $request->title,
            'url' => $request->url,
            'remarks' => $request->remarks,
        ]);
        $lesson = Lesson::findOrFail($request->lesson_id);
        return response()->json([
            'result' => true,
            'data' => new LessonUpdateResource($lesson)
        ]);
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
}
