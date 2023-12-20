<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Instructor;
use App\Model\Chapter;
use App\Http\Requests\Manager\ChapterShowRequest;
use App\Http\Resources\Manager\ChapterShowResource;

class ChapterController extends Controller
{
    /**
     * チャプター詳細情報を取得
     *
     * @param ChapterShowRequest $request
     * @return ChapterShowResource
     */
    public function show(ChapterShowRequest $request)
    {
        // ユーザーID取得
        $userId = $request->user()->id;
        // ユーザーIDから配下のinstructorを取得
        $manager = Instructor::with('managings')->find($userId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $userId;
        // chapter_idから属するlassons含めてデータ取得
        $chapter = Chapter::with(['lessons','course'])->findOrFail($request->chapter_id);
        // 自身もしくは配下のinstructorでない場合はエラー応答
        if (!in_array($chapter->course->instructor_id, $instructorIds, true)) {
            return response()->json([
                'result' => false,
                'message' => "Forbidden, not allowed to edit this course.",
            ], 403);
        }

        return new ChapterShowResource($chapter);
    }
}
