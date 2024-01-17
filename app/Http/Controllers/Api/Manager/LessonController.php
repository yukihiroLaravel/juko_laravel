<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Http\Requests\Manager\LessonUpdateRequest;
use App\Http\Requests\Manager\LessonSortRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Exception;

class LessonController extends Controller
{
    /**
     * マネージャ配下のレッスン更新API
     *
     * @param  LessonUpdateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(LessonUpdateRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        if (!in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
            // 配下の講師でない場合は403エラー
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to edit this lesson.",
            ], 403);
        }

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            // コースIDが不正な場合は403エラー
            return response()->json([
                'result' => false,
                'message' => 'Invalid course_id.',
            ], 403);
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            // チャプターIDが不正な場合は403エラー
            return response()->json([
                'result' => false,
                'message' => 'Invalid chapter_id.',
            ], 403);
        }

        $lesson->update([
            'title' => $request->title,
            'url' => $request->url,
            'remarks' => $request->remarks,
            'status' => $request->status,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * レッスン並び替えAPI
     *
     * @param  LessonSortRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sort(LessonSortRequest $request)
    {
        try {
            // 現在のユーザーを取得
            $instructorId = Auth::guard('instructor')->user()->id;

            // マネージャーが管理する講師を取得
            $manager = Instructor::with('managings')->find($instructorId);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $instructorId;

            $courseId = $request->input('course_id');
            $chapterId = $request->input('chapter_id');
            $inputLessons = $request->input('lessons');

            // レッスンを一括取得
            $lessons = Lesson::with('chapter.course')->whereIn('id', array_column($inputLessons, 'lesson_id'))->get();

            /// 認可
            $lessons->each(function ($lesson) use ($instructorIds, $courseId, $chapterId) {
                if (!in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
                    return response()->json([
                        'result' => false,
                        'message' => 'Invalid instructor_id.',
                    ], 403);
                }

                if ((int) $courseId !== $lesson->chapter->course->id) {
                    return response()->json([
                        'result' => false,
                        'message' => 'Invalid course.',
                    ], 403);
                }

                if ((int) $chapterId !== $lesson->chapter->id) {
                    return response()->json([
                        'result' => false,
                        'message' => 'Invalid chapter.',
                    ], 403);
                }
            });

            // レッスンのorderカラムを更新
            $lessons->each(function ($lesson) use ($inputLessons) {
                $collectionLessons = new Collection($inputLessons);
                $inputLesson = $collectionLessons->firstWhere('lesson_id', $lesson->id);
                $lesson->update([
                    'order' => $inputLesson['order'],
                ]);
            });

            return response()->json([
                'result' => true,
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
