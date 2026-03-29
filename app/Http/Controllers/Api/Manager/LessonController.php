<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Lesson\BulkDeleteRequest;
use App\Model\Lesson;
use App\Services\Lesson\BulkDeleteLessonsService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Manager-Lesson
 */
class LessonController extends Controller
{
    /**
     * 選択済みレッスン削除API
     */
    public function bulkDelete(BulkDeleteRequest $request, BulkDeleteLessonsService $service): JsonResponse
    {
        // リクエストからデータを取得
        $lessonIds = $request->input('lessons');
        $chapterId = $request->input('chapter_id');

        // レッスン情報を取得
        $lessons = Lesson::with('chapter.course', 'lessonAttendances')->whereIn('id', $lessonIds)->get();
        DB::beginTransaction();

        try {
            // 自身もしくは配下の講師の講座・チャプターに紐づくレッスンでない場合は許可しない
            $this->authorize('bulkDelete', [Lesson::class, $lessons]);

            $lessons->each(function (Lesson $lesson) use ($chapterId) {
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ((int) $chapterId !== $lesson->chapter->id) {
                    throw new AuthorizationException('Invalid chapter_id.');
                }
                // 受講情報が登録されている場合は許可しない
                if ($lesson->lessonAttendances->isNotEmpty()) {
                    throw new AuthorizationException('This lesson has attendance.');
                }
            });

            // サービスクラスで対象レッスンの削除処理を実行
            $service(
                lessonIds: $lessons->pluck('id')->toArray(),
                chapterId: $chapterId
            );

            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }
}
