<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Http\Requests\Manager\LessonUpdateRequest;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
    /**
     * マネージャ配下のレッスン更新API
     *
     *
     *@param  LessonUpdateRequest $request
     */
    public function update(LessonUpdateRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        if (!in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座のチャプターレッスンでなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to edit this lesson.",
            ], 403);
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id ) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid chapter_id.',
            ], 403);
        }

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid course_id.',
            ], 403);
        }

        $lesson->update([
            'title' => $request->title,
            'url' => $request->url,
            'remarks' => $request->remarks,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }
}
