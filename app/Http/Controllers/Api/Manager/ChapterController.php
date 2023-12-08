<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Model\Instructor;
use App\Model\Chapter;


class ChapterController extends Controller
{
    /**
      * マネージャ配下のチャプター更新API
      *
      */
      public function update(Request $request)
    {
        // 現在のユーザーを取得
        $instructorId = Auth::guard('instructor')->user()->id;
        
        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        //自分と配下のnstructorのチャプターでなければエラー応答
        $chapter = Chapter::FindOrFail($request->chapter_id);
        if (!in_array($chapter->instructor_id, $instructorIds, true)) {
            // エラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to edit this course.",
            ], 403);
        }  

        return response()->json($chapter); // 更新されたコース情報を返す
    }
}