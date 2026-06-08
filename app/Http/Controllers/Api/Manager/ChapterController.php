<?php

namespace App\Http\Controllers\Api\Manager;

use App\Enums\Chapter\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Chapter\DeleteRequest;
use App\Http\Requests\Manager\Chapter\UpdateStatusRequest;
use App\Model\Chapter;
use App\Model\Instructor;
use App\Services\Chapter\DeleteChapterService;
use App\Services\Chapter\StatusTransitionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @tags Manager-Chapter
 */
class ChapterController extends Controller
{
    /**
     * チャプター削除API(単一)
     */
    public function delete(DeleteRequest $request, DeleteChapterService $deleteChapterService): JsonResponse
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // チャプターを取得
        $chapter = Chapter::with(['course', 'lessons'])->findOrFail($request->chapter_id);

        if (! in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座のチャプターでなければエラー応答
            throw new AuthorizationException('Forbidden, not allowed to delete this chapter.');
        }

        // チャプター内に受講中のレッスンがあるか確認
        if ($chapter->lessons()->whereHas('lessonAttendances')->exists()) {
            // 指定したチャプター内に受講中のレッスンがあればエラー応答
            throw new AuthorizationException('Forbidden, this lesson has attendance.');
        }

        $chapter->delete();
        // チャプター削除
        $deleteChapterService($chapter);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * チャプターの公開状態を更新するAPI
     */
    public function updateStatus(UpdateStatusRequest $request, StatusTransitionService $service): JsonResponse
    {
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        // Policyによる認可処理
        $this->authorize('update', $chapter);

        $targetStatus = StatusEnum::from($request->status);

        // ステータスの変更が許可されるかどうかを検証
        $service(current: $chapter->status, target: $targetStatus);

        // チャプターのステータスを更新
        $chapter->update([
            'status' => $targetStatus->value,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }
}
