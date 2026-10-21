<?php

namespace App\Http\Controllers\Api\Manager;

use App\Enums\Chapter\StatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Chapter\DeleteRequest;
use App\Http\Requests\Manager\Chapter\UpdateStatusRequest;
use App\Model\Chapter;
use App\Services\Chapter\DeleteChapterService;
use App\Services\Chapter\StatusTransitionService;
use Illuminate\Http\JsonResponse;

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
        // チャプターを取得
        $chapter = Chapter::with(['course', 'lessons'])->findOrFail($request->chapter_id);

        // Policyによる認可処理
        $this->authorize('delete', $chapter);

        // チャプター削除（受講中レッスンの有無チェックはサービス内で実施）
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
