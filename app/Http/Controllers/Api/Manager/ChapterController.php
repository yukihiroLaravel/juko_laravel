<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\ChapterPatchRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Model\Instructor;
use App\Model\Chapter;

class ChapterController extends Controller
{
    /**
      * マネージャー配下のチャプター更新API
      * @param ChapterPatchRequest $request
      * @return \Illuminate\Http\JsonResponse
      */
    public function update(ChapterPatchRequest $request)
    {
        // 現在のユーザーを取得
        $instructorId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        // チャプターを取得
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        // マネージャー自身が作成したチャプターか、または配下の講師が作成したチャプターなら更新を許可
        if (!in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // 失敗結果を返す
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to edit this chapter.",
            ], 403);
        }

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
