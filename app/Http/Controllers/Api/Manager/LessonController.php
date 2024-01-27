<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\Course;
use App\Model\LessonAttendance;
use App\Http\Requests\Manager\LessonUpdateRequest;
use App\Http\Requests\Manager\LessonSortRequest;
use App\Http\Requests\Manager\LessonDeleteRequest;
use App\Http\Requests\Manager\LessonStoreRequest;
use App\Http\Resources\Manager\LessonStoreResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\ValidationErrorException;
use Illuminate\Support\Facades\Log;
use Exception;

class LessonController extends Controller
{
    /**
     * レッスン新規作成API
     *
     * @param LessonStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(LessonStoreRequest $request)
    {
        $instructorId = Auth::guard('instructor')->user()->id;
        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->findOrfail($instructorId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $instructorId;

        $course = Course::find($request->course_id);

        if (!in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座でなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to create new lesson.",
            ], 403);
        }

        $maxOrder = Lesson::where('chapter_id', $request->chapter_id)->max('order');

        try {
            $newLesson = Lesson::create([
                'chapter_id' => $request->chapter_id,
                'title' => $request->title,
                'status' => Lesson::STATUS_PRIVATE,
                'order' => (int) $maxOrder + 1
            ]);

            return response()->json([
                "result" => true,
                "data" => new LessonStoreResource($newLesson),
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                "result" => false,
            ]);
        }
    }

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
            // 講座IDが不正な場合は403エラー
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
            // 指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
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
                // 講座に紐づく講師でない場合は許可しない
                if (!in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
                    throw new ValidationErrorException('Invalid instructor_id.');
                }
                // 指定した講座IDが1レッスンの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $lesson->chapter->course->id) {
                    throw new ValidationErrorException('Invalid course.');
                }
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
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
            'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
            "result" => false,
            ]);
        }
    }
}
