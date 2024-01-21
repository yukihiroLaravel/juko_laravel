<?php

namespace App\Http\Controllers\Api\Manager;

use App\Model\Instructor;
use App\Model\Course;
use App\Model\Chapter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\ChapterPatchRequest;
use App\Http\Requests\Manager\ChapterDeleteRequest;
use App\Http\Requests\Manager\ChapterSortRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class ChapterController extends Controller
{
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
<<<<<<< Updated upstream
=======

    /**
     * チャプター並び替えAPI
     *
     * @param ChapterSortRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function sort(ChapterSortRequest $request)
    {
        // チャプターをソートする
        DB::beginTransaction();
        try {
            // 現在のユーザーを取得
            $userId = Auth::guard('instructor')->user()->id;
            $courseId = $request->input('course_id');
            $chapters = $request->input('chapters');
            $course = Course::findOrFail($courseId);

            // マネージャーが管理する講師を取得
            $manager = Instructor::with('managings')->find($userId);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $userId;

            // マネージャー自身または配下の講師が担当する講座なら更新を許可
            if (!in_array($course->instructor_id, $instructorIds, true)) {
                // 失敗結果を返す
                return response()->json([
                    'result'  => false,
                    'message' => "Forbidden, not allowed to edit this chapter.",
                ], 403);
            }
            foreach ($chapters as $chapter) {
                Chapter::where('id', $chapter['chapter_id'])
                ->where('course_id', $courseId)
                ->firstOrFail()
                ->update([
                    'order' => $chapter['order']
                ]);
            }
            DB::commit();
            return response()->json([
                'result' => true,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'result' => false,
                'message' => 'Not found course.'
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }
>>>>>>> Stashed changes
}
