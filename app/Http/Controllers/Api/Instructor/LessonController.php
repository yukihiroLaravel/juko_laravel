<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonStoreRequest;
use App\Http\Resources\Instructor\LessonStoreResource;
use App\Http\Requests\Instructor\LessonUpdateRequest;
use App\Http\Resources\Instructor\LessonUpdateResource;
use App\Model\Lesson;
use App\Model\Instructor;
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
                'status' => Lesson::STATUS_PRIVATE,
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
        $user = Instructor::find(1);
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        if ($lesson->chapter->course->instructor_id !== $user->id) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid instructor_id',
            ], 403);
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id || (int) $request->course_id !== $lesson->chapter->course_id) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid chapter_id or course_id.',
            ], 403);
        }
 
        $lesson->update([
            'title' => $request->title,
            'url' => $request->url,
            'remarks' => $request->remarks,
            'status' => $request->status,
        ]);

        return response()->json([
            'result' => true,
            'data' => new LessonUpdateResource($lesson->refresh())
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
