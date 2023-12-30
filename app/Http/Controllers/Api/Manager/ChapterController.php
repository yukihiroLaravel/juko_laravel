<?php

namespace App\Http\Controllers\Api\Manager;

use App\Model\Instructor;
use App\Model\Course;
use App\Model\Chapter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\ChapterStoreRequest;
use App\Http\Requests\Manager\ChapterDeleteRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChapterController extends Controller
{
    /**
     * チャプター新規作成API
     *
     * @param ChapterStoreRequest $request
     */
    public function store(ChapterStoreRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $course = Course::FindOrFail($request->course_id);

        if (!in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座でなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to create new chapter.",
            ], 403);
        }

        $order =  $course->chapters->count();
        $newOrder = $order + 1;
        Chapter::create([
            'course_id' => $request->input('course_id'),
            'title' => $request->input('title'),
            'order' => $newOrder,
            'status' => Chapter::STATUS_PUBLIC,
         ]);

        return response()->json([
            'result' => true,
        ]);
    }
    /**
     * マネージャ配下のチャプター削除API
     *
     */
    public function delete(ChapterDeleteRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        if (!in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座のチャプターでなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to delete this chapter.",
            ], 403);
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座に属するチャプターでなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => 'Invalid course_id.',
            ], 403);
        }

        $chapter->delete();
        return response()->json([
            "result" => true
        ]);
    }
}
