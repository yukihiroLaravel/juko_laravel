<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Lesson;
use App\Model\Instructor;
use App\Model\LessonAttendance;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LessonController extends Controller
{
    /**
     * レッスン削除API
     *
     * @param LessonDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(Request $request)
    {
        DB::beginTransaction();
        try{
            // 自身と配下のinstructor情報を取得
            $userId = $request->user()->id;
            $lesson = Lesson::with('chapter')->findOrFail($request->lesson_id);
            $manager = Instructor::with('managings')->find($userId);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $userId;
            // 自身もしくは配下のinstructorの講座・チャプターに紐づくレッスンでない場合は許可しない
            if (!in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
                return response()->json([
                    'result'  => false,
                    'message' => 'Invalid instructor_id.',
                ], 403);
            }
            // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
            if ((int)$request->chapter_id !== $lesson->chapter->id) {
                return response()->json([
                    'result' => false,
                    'message' => 'Invalid chapter_id.',
                ], 403);
            }
            // 受講情報が登録されている場合は許可しない
            if (LessonAttendance::where('lesson_id', $lesson->id)->exists()) {
                return response()->json([
                    'result' => false,
                    'message' => 'This lesson has attendance.',
                ], 403);
            }
            // 対象レッスンの削除処理
            $lesson->update(['order' => 0]);
            $lesson->delete();
            Lesson::where('chapter_id', $lesson->chapter_id)
                ->orderBy('order')
                ->get()
                ->each(function ($lesson, $index) {
                    $lesson->update(['order' => $index + 1]);
                });
            DB::commit();
            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }
}
