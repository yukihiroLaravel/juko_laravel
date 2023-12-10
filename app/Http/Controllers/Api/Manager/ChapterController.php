<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

        // チャプターを取得
        $chapter = Chapter::findOrFail($request->chapter_id);

        // チャプターを作成した講師IDを取得
        $chapterInstructorId = $chapter->id;

        // マネージャー自身が作成したチャプターか、または配下の講師が作成したチャプターなら更新を許可
        if (!in_array($chapterInstructorId, $instructorIds, true)) {
            // 失敗結果を返す
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to edit this chapter.",
            ], 403);
        }

        Log::info('Request Data:', $request->all());

        // チャプターを更新する
        $chapter->update([
            'title' => $request->title,
        ]);

        // 成功結果を返す
        return response()->json([
            'result'  => true,
        ]);
    }
}