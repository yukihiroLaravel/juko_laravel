<?php

namespace App\Http\Controllers\Api\Manager;

use Exception;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\Attendance;
use App\Model\Instructor;
use App\Model\LessonAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\ValidationErrorException;
use App\Http\Requests\Manager\LessonSortRequest;
use App\Http\Requests\Manager\LessonStoreRequest;
use App\Http\Requests\Manager\LessonDeleteRequest;
use App\Http\Requests\Manager\LessonUpdateRequest;
use App\Http\Requests\Manager\LessonPutStatusRequest;
use App\Http\Requests\Manager\LessonBulkDeleteRequest;
use App\Http\Requests\Manager\LessonUpdateTitleRequest;

class LessonController extends Controller
{
    /**
     * レッスン新規作成API
     *
     * @param LessonStoreRequest $request
     * @return JsonResponse
     */
    public function store(LessonStoreRequest $request)
    {
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrfail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $course = Course::find($request->course_id);

        if (!in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座でなければエラー応答
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to create new lesson.",
            ], 403);
        }

        $maxOrder = Lesson::where('chapter_id', $request->chapter_id)->max('order');

        DB::beginTransaction();
        try {
            $lesson = Lesson::create([
                'chapter_id' => $request->chapter_id,
                'title' => $request->title,
                'status' => Lesson::STATUS_PRIVATE,
                'order' => (int) $maxOrder + 1
            ]);

            $attendances = Attendance::where('course_id', $request->course_id)->get();
            $lesson_id = $lesson->id;
            $attendances->each(function ($attendance) use (&$lesson_id) {
                LessonAttendance::create([
                    'attendance_id' => $attendance->id,
                    'lesson_id'     => $lesson_id,
                    'status'        => LessonAttendance::STATUS_BEFORE_ATTENDANCE
                ]);
            });

            DB::commit();
            return response()->json([
                "result" => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);
        }
    }

    /**
     * レッスン更新API
     *
     * @param  LessonUpdateRequest $request
     * @return JsonResponse
     */
    public function update(LessonUpdateRequest $request)
    {
        $managerId = Auth::guard('instructor')->user()->id;
        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        if (!in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
            // 配下の講師でない場合は403エラー
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to this lesson.",
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
            $managerId = Auth::guard('instructor')->user()->id;

            /** @var Instructor $manager */
            $manager = Instructor::with('managings')->find($managerId);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $manager->id;

            // レッスン情報を取得
            /** @var Lesson $lesson */
            $lesson = Lesson::with('chapter')->findOrFail($request->lesson_id);

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
     * @return JsonResponse
     */
    public function sort(LessonSortRequest $request)
    {
        DB::beginTransaction();

        try {
            $managerId = Auth::guard('instructor')->user()->id;

            // マネージャーが管理する講師を取得
            /** @var Instructor $manager */
            $manager = Instructor::with('managings')->find($managerId);
            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $manager->id;

            $courseId = $request->input('course_id');
            $chapterId = $request->input('chapter_id');
            $inputLessons = $request->input('lessons');

            // レッスンを一括取得
            $lessons = Lesson::with('chapter.course')->whereIn('id', array_column($inputLessons, 'lesson_id'))->get();

            /// 認可
            $lessons->each(function (Lesson $lesson) use ($instructorIds, $courseId, $chapterId) {
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
            $lessons->each(function (Lesson $lesson) use ($inputLessons) {
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

    /**
     * レッスンタイトル変更API
     *
     * @param LessonUpdateTitleRequest $request
     * @return JsonResponse
     */
    public function updateTitle(LessonUpdateTitleRequest $request)
    {
        // 現在のユーザーを取得（講師の場合）
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーが管理する講師を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定されたレッスンを取得
        /** @var Lesson $lesson */
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        // 自分、または配下の講師の講座のレッスンでなければエラー応答
        if (!in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
            return response()->json([
                'result' => false,
                'message' => 'Unauthorized access to update lesson title.'
            ], 403);
        }

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid course_id.',
            ], 403);
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid chapter_id.',
            ], 403);
        }

        $lesson->update([
            'title' => $request->title
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * 選択済みレッスンステータス一括更新API
     *
     * @param LessonPutStatusRequest $request
     * @return JsonResponse
     */
    public function putStatus(LessonPutStatusRequest $request): JsonResponse
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        //配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        //リクエストからデータを取得
        $lessonIds = $request->input('lessons');
        $chapterId = $request->input('chapter_id');
        $courseId = $request->input('course_id');
        $status = $request->input('status');

        // レッスン情報を取得
        /** @var Lesson $lesson */
        $lesson = Lesson::with('chapter.course', 'lessonAttendances')->whereIn('id', $lessonIds)->get();
        try {
            $lessons->each(function (Lesson $lesson) use ($instructorIds, $chapterId, $courseId) {
                if (!in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
                //講座に紐づく講師でない場合は許可しない
                    throw new AuthorizationException('Invalid instructor_id.');
                }
                if ((int)$courseId !== $lesson->chapter->course->id) {
                //指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
                    throw new AuthorizationException('Invalid course_id.');
                }
                if ((int)$chapterId !== $lesson->chapter_id) {
                //指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                    throw new AuthorizationException('Invalid chapter_id.');
                }
            });

            // レッスンのステータスを一括更新
            Lesson::whereIn('id', $lessons->pluck('id')->toArray())->update(['status' => $status]);

            return response()->json([
                'result' => true,
            ]);
        } catch (AuthorizationException $e) {
            // エラーハンドリング、認可に失敗した場合エラーを返す
            return response()->json([
                'result' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }


    /**
     * 選択済みレッスン削除API
     *
     * @param LessonBulkDeleteRequest $request
     * @return JsonResponse
     */
    public function bulkDelete(LessonBulkDeleteRequest $request): JsonResponse
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        //リクエストからデータを取得
        $lessonIds = $request->input('lessons');
        $chapterId = $request->input('chapter_id');
        $courseId =  $request->input('course_id');

        // レッスン情報を取得
        /** @var Lesson $lesson */
        $lesson = Lesson::with('chapter.course', 'lessonAttendances')->whereIn('id', $lessonIds)->get();
        try {
            $lesson->each(function (Lesson $lesson) use ($instructorIds, $chapterId, $courseId) {
                // 自身もしくは配下の講師の講座・チャプターに紐づくレッスンでない場合は許可しない
                if (!in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
                    throw new ValidationErrorException('Invalid instructor_id.');
                }
                // 指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $lesson->chapter->course->id) {
                    throw new ValidationErrorException('Invalid course_id.');
                }
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ((int) $chapterId !== $lesson->chapter->id) {
                    throw new ValidationErrorException('Invalid chapter_id.');
                }
                // 受講情報が登録されている場合は許可しない
                if ($lesson->lessonAttendances->isNotEmpty()) {
                    throw new ValidationErrorException('This lesson has attendance.');
                }
            });

            DB::beginTransaction();

            Lesson::whereIn('id', $lessonIds)->update(['order' => 0]);
            Lesson::whereIn('id', $lessonIds)->delete();
            //レッスン順序の更新
            Lesson::where('chapter_id', $chapterId)
                ->orderBy('order')
                ->get()
                ->each(function (Lesson $lesson, int $index) {
                    $lesson->update(['order' => $index + 1]);
                });
            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (ValidationErrorException $e) {
            return response()->json([
                'result' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'result' => false,
                'message' => 'Failed to delete lesson.',
            ], 500);
        }
    }
}
