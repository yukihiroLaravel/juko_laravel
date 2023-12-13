<?php

namespace App\Http\Controllers\Api\Manager;

use App\Model\Instructor;
use App\Model\Course;
use App\Model\Chapter;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;

class ChapterController extends Controller
{
    /**
      * マネージャ配下のチャプター削除API
      *
      */
    public function delete(Request $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $course = Course::FindOrFail($request->course_id);
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);
        
        if (!in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座のチャプターでなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to delete this chapter.",
            ], 403);
        }
        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定したコースに属するチャプターでなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => 'invalid course_id.',
            ], 403);
        }
        $chapter->delete();
        return response()->json([
            "result" => true
        ]);
    }
}
