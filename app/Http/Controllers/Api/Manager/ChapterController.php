<?php

namespace App\Http\Controllers\Api\Manager;

use App\Enums\Chapter\StatusEnum;
use App\Exceptions\ValidationErrorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Chapter\DeleteRequest;
use App\Http\Requests\Manager\Chapter\UpdateStatusRequest;
use App\Model\Chapter;
use App\Model\Instructor;
use App\Services\Chapter\DeleteChapterService;
use App\Services\Chapter\StatusTransitionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
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

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座に属するチャプターでなければエラー応答
            throw new AuthorizationException('Forbidden, invalid course_id.');
        }

        // チャプター削除
        $deleteChapterService($chapter);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * チャプターの公開状態を更新するAPI
     */
    public function updateStatus(UpdateStatusRequest $request): JsonResponse
    {
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        // course_idの整合性チェック（講座に属しているか確認）
        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座に属するチャプターでなければエラー応答
            throw new ValidationErrorException('Forbidden, invalid course_id.');
        }

        // Policyによる認可処理
        $this->authorize('update', $chapter);

        $targetStatus = StatusEnum::from($request->status);

        // ステータスの変更が許可されるかどうかを検証
        /** @var Collection<int, Chapter> $chapters */
        $chapters = new Collection([$chapter]);
        $service = new StatusTransitionService;
        $service($chapters, $targetStatus);

        // チャプターのステータスを更新
        $chapter->update([
            'status' => $targetStatus->value,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }
}
