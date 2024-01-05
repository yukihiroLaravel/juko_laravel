<?php

namespace App\Http\Controllers\Api\Manager;

use App\Model\Instructor;
use App\Model\Chapter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\ChapterShowRequest;
use App\Http\Requests\Manager\ChapterPatchRequest;
use App\Http\Requests\Manager\ChapterDeleteRequest;
use App\Http\Resources\Manager\ChapterShowResource;
use Illuminate\Support\Facades\Auth;

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

    /**
     * マネージャー配下のチャプター更新API
     *
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

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座IDがチャプターの講座IDと一致しない場合は更新を許可しない
            return response()->json([
                'result'  => false,
                'message' => 'Invalid course_id.',
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

    /**
     * マネージャ配下のチャプター削除API
     *
     * @param ChapterDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
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

    /**
       * マネージャ配下のチャプター更新API
       *
       */
      public function status()
      {
          return response()->json([]);
      }
}
