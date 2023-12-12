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
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        //自分と配下のインストラクターが持っている講座を取得
        $course = Course::FindOrFail($request->course_id);

        if (!in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座のチャプターでなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to delete this chapter.",
            ], 403);
        }

        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);
        
        $chapter->delete();
        return response()->json([
            "result" => true
        ]);
    }
}
