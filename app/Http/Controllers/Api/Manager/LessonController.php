<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\Course;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Manager\LessonStoreRequest;
use App\Http\Resources\Manager\LessonStoreResource;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
   /**
    * レッスン新規作成API
    *
    * @param LessonStoreRequest $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function store(LessonStoreRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->findOrfail($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $course = Course::find($request->course_id);

        if (!in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座でなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to create new lesson.",
            ], 403);
        }

        $maxOrder = Lesson::where('chapter_id', $request->chapter_id)->max('order');

        try {
            $newLesson = Lesson::create([
                'chapter_id' => $request->chapter_id,
                'title' => $request->title,
                'status' => Lesson::STATUS_PRIVATE,
                'order' => (int) $maxOrder + 1
            ]);

            return response()->json([
                "result" => true,
                "data" => new LessonStoreResource($newLesson),
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);
        }
    }
}
