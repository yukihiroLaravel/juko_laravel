<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\LessonSortRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Model\Lesson;
use App\Model\Instructor;
use Exception;


class LessonController extends Controller
{
        /**
     * レッスン並び替えAPI
     *
     * @param  LessonSortRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sort(LessonSortRequest $request)
    {
        DB::beginTransaction();

        try {
            // 現在のユーザーを取得
            $instructorId = Auth::guard('instructor')->user()->id;

            // マネージャーが管理する講師を取得
            $manager = Instructor::with('managings')->find($instructorId);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $instructorId;

            // リクエストから受け取ったレッスン情報を取得し、ループ処理を行う
            $inputLessons = $request->input('lessons');
            foreach ($inputLessons as $inputLesson) {
                // レッスンIDを使用して、関連するレッスンを取得
                $lesson = Lesson::with('chapter.course')->findOrFail($inputLesson['lesson_id']);

                // マネージャーまたは配下の講師が作成したレッスンかを確認
                if (!in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
                    // 失敗結果を返す
                    return response()->json([
                        'result'  => false,
                        'message' => "Forbidden, not allowed to edit this lesson.",
                    ], 403);
                }

                // レッスンが指定されたチャプターおよびコースに関連付けられていることを確認
                if (
                    (int) $request->chapter_id !== $lesson->chapter->id ||
                    (int) $request->course_id !== $lesson->chapter->course_id
                ) {
                    throw new Exception('Invalid lesson.');
                }

                // レッスンの並び替えを実行
                $lesson->update([
                    'order' => $inputLesson['order']
                ]);
            }

            DB::commit();

            return response()->json([
                "result" => true
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                "result" => false,
            ]);
        }
    }
}