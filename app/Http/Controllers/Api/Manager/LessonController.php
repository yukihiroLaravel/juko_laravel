<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\LessonUpdateRequest;
use App\Http\Requests\Manager\LessonSortRequest;
use App\Http\Requests\Manager\LessonDeleteRequest;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Exceptions\ValidationErrorException;

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
        DB::beginTransaction();

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
                    throw new ValidationErrorException('Invalid instructor_id.');
                }

                if ((int) $courseId !== $lesson->chapter->course->id) {
                    throw new ValidationErrorException('Invalid course.');
                }

                if ((int) $chapterId !== $lesson->chapter->id) {
                    throw new ValidationErrorException('Invalid chapter.');
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

            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (ValidationErrorException $e) {
            return response()->json([
                'result' => false,
                'error' => $e->getMessage(),
            ], 403);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                "result" => false,
            ]);
        }
    }

    /**
     * レッスン削除API
     *
     * @param LessonDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(LessonDeleteRequest $request)
    {
        DB::beginTransaction();
        try {
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
            // 指定したコースIDがレッスンのコースIDと一致しない場合は許可しない
            if ((int)$request->course_id !== $lesson->chapter->course_id) {
                return response()->json([
                    'result' => false,
                    'message' => 'Invalid course_id.',
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
