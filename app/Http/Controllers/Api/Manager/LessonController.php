<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
   /**
     * マネージャー
     * レッスン新規作成API
     *
     */
    public function store(Request $request)
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


        try {
            $newlesson = Lesson::create([
                'chapter_id' => $request->chapter_id,
                'title' => $request->title,
                'status' => Lesson::STATUS_PRIVATE,
                'order' => 1
            ]);
            // 新しく作成されたレッスンのlesson_idを取得
            $lessonId = $newlesson->id;

            return response()->json([
                "result" => true,
                "data" => Lesson::findOrFail($lessonId),
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);
        }
    }
}
