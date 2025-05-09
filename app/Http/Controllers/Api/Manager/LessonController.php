<?php

namespace App\Http\Controllers\Api\Manager;

use App\Exceptions\ValidationErrorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Lesson\BulkDeleteRequest;
use App\Http\Requests\Manager\Lesson\DeleteAllRequest;
use App\Http\Requests\Manager\Lesson\DeleteRequest;
use App\Http\Requests\Manager\Lesson\PutRequest;
use App\Http\Requests\Manager\Lesson\PutStatusRequest;
use App\Http\Requests\Manager\Lesson\SortRequest;
use App\Http\Requests\Manager\Lesson\StoreRequest;
use App\Http\Requests\Manager\Lesson\UpdateStatusRequest;
use App\Http\Requests\Manager\Lesson\UpdateTitleRequest;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\services\Lesson\UpdateLessonStatusService;
use App\Model\LessonAttendance;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Manager-Lesson
 */
class LessonController extends Controller
{
    /**
     * レッスン新規作成API
     *
     * @return JsonResponse
     */
    public function store(StoreRequest $request)
    {
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $course = Course::find($request->course_id);

        if (! in_array($course->instructor_id, $instructorIds, true)) {
            // 自分、または配下の講師の講座でなければエラー応答
            throw new AuthorizationException('Forbidden, not allowed to this lesson.');
        }

        $maxOrder = Lesson::where('chapter_id', $request->chapter_id)->max('order');

        DB::beginTransaction();
        try {
            $lesson = Lesson::create([
                'chapter_id' => $request->chapter_id,
                'title' => $request->title,
                'status' => Lesson::STATUS_PRIVATE,
                'order' => (int) $maxOrder + 1,
            ]);
            assert($lesson instanceof Lesson);

            $attendances = Attendance::where('course_id', $request->course_id)->get();
            $lessonId = $lesson->id;
            $attendances->each(function (Attendance $attendance) use ($lessonId) {
                LessonAttendance::create([
                    'attendance_id' => $attendance->id,
                    'lesson_id' => $lessonId,
                    'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
                ]);
            });

            DB::commit();

            return response()->json([
                'result' => true,
                'lesson_id' => $lesson->id,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * レッスン更新API
     *
     * @return JsonResponse
     */
    public function put(PutRequest $request)
    {
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        $manager = Instructor::with('managings')->find($managerId);
        assert($manager instanceof Instructor);

        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);
        assert($lesson instanceof Lesson);

        if (! in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
            // 配下の講師でない場合は403エラー
            throw new AuthorizationException('Forbidden, not allowed to this lesson.');
        }

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            // 講座IDが不正な場合は403エラー
            throw new AuthorizationException('Invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            // チャプターIDが不正な場合は403エラー
            throw new AuthorizationException('Invalid chapter_id.');
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
     * @return JsonResponse
     */
    public function delete(DeleteRequest $request)
    {
        DB::beginTransaction();
        try {
            // 自身と配下のinstructor情報を取得
            $managerId = Auth::guard('instructor')->user()->id;

            $manager = Instructor::with('managings')->find($managerId);
            assert($manager instanceof Instructor);

            $instructorIds = $manager->managings->pluck('id')->toArray();
            $instructorIds[] = $manager->id;

            // レッスン情報を取得
            /** @var Lesson $lesson */
            $lesson = Lesson::with('chapter')->findOrFail($request->lesson_id);

            // 自身もしくは配下のinstructorの講座・チャプターに紐づくレッスンでない場合は許可しない
            if (! in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
                throw new AuthorizationException('Forbidden, not allowed to delete this lesson.');
            }

            // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
            if ((int) $request->chapter_id !== $lesson->chapter->id) {
                throw new AuthorizationException('Invalid chapter_id.');
            }

            // 指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
            if ((int) $request->course_id !== $lesson->chapter->course_id) {
                throw new AuthorizationException('Invalid course_id.');
            }

            // 受講情報が登録されている場合は許可しない
            if (LessonAttendance::where('lesson_id', $lesson->id)->exists()) {
                throw new AuthorizationException('Forbidden, not allowed to delete this lesson.');
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
            throw $e;
        }
    }

    /**
     * レッスン並び替えAPI
     *
     * @return JsonResponse
     */
    public function sort(SortRequest $request)
    {
        DB::beginTransaction();

        try {
            $managerId = Auth::guard('instructor')->user()->id;

            // マネージャーが管理する講師を取得
            $manager = Instructor::with('managings')->find($managerId);
            assert($manager instanceof Instructor);

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
                if (! in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
                    throw new AuthorizationException('Forbidden, not allowed to delete this lesson. Invalid instructor.');
                }
                // 指定した講座IDが1レッスンの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $lesson->chapter->course->id) {
                    throw new AuthorizationException('Forbidden, not allowed to delete this lesson. Invalid course_id.');
                }
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ((int) $chapterId !== $lesson->chapter->id) {
                    throw new AuthorizationException('Forbidden, not allowed to delete this lesson. Invalid chapter_id.');
                }
            });

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
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * レッスンステータス更新API
     */
    public function updateStatus(lesson $request, UpdateLessonStatusService $updateLessonStatusService): JsonResponse
    {
        $managerId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 指定されたレッスンを取得
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        if (! in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
            // 自身もしくは配下の講師の講座でなければエラー応答
            throw new ValidationErrorException('Invalid instructor_id.');
        }

        if ((int) $request->course_id !== $lesson->chapter->course->id) {
            // 指定した講座IDがレッスンの講座IDと一致しない場合は更新を許可しない
            throw new ValidationErrorException('Invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は更新を許可しない
            throw new ValidationErrorException('Invalid chapter_id.');
        }

        $lesson = Lesson::findOrFail($request->lesson_id);
        // サービスの呼び出し（関数のように使える）
        $updateLessonStatusService($lesson, $request->status);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * レッスンタイトル変更API
     *
     * @return JsonResponse
     */
    public function updateTitle(UpdateTitleRequest $request)
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
        if (! in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
            throw new ValidationErrorException('Unauthorized access to update lesson title.');
        }

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            throw new ValidationErrorException('Invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            throw new ValidationErrorException('Invalid chapter_id.');
        }

        $lesson->update([
            'title' => $request->title,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * 選択済みレッスンステータス一括更新API
     */
    public function putStatus(PutStatusRequest $request): JsonResponse
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // リクエストからデータを取得
        $lessonIds = $request->input('lessons');
        $chapterId = $request->input('chapter_id');
        $courseId = $request->input('course_id');
        $status = $request->input('status');

        //レッスンデータの取得
        $lessons = Lesson::with('chapter.course')->whereIn('id', $lessonIds)->get();
        try {
            $lessons->each(function (Lesson $lesson) use ($instructorIds, $chapterId, $courseId) {
                if (! in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
                    //講座に紐づく講師でない場合は許可しない
                    throw new AuthorizationException('Invalid instructor_id.');
                }
                if ((int) $courseId !== $lesson->chapter->course->id) {
                    //指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
                    throw new AuthorizationException('Invalid course_id.');
                }
                if ((int) $chapterId !== $lesson->chapter_id) {
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
     */
    public function bulkDelete(BulkDeleteRequest $request): JsonResponse
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // リクエストからデータを取得
        $lessonIds = $request->input('lessons');
        $chapterId = $request->input('chapter_id');
        $courseId = $request->input('course_id');

        // レッスン情報を取得
        /** @var Lesson $lesson */
        $lesson = Lesson::with('chapter.course', 'lessonAttendances')->whereIn('id', $lessonIds)->get();
        try {
            DB::beginTransaction();

            $lesson->each(function (Lesson $lesson) use ($instructorIds, $chapterId, $courseId) {
                // 自身もしくは配下の講師の講座・チャプターに紐づくレッスンでない場合は許可しない
                if (! in_array($lesson->chapter->course->instructor_id, $instructorIds, true)) {
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
            throw $e;
        }
    }

    /**
     * チャプターに紐づく全レッスンを削除するAPI
     */
    public function deleteAll(DeleteAllRequest $request): JsonResponse
    {
        // ログイン中の講師IDを取得
        $managerId = Auth::guard('instructor')->user()->id;

        // マネージャーと その管理下の講師を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->find($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // チャプターを取得
        /** @var Chapter $chapter */
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        if (! in_array($chapter->course->instructor_id, $instructorIds, true)) {
            // ログイン中のマネージャーまたはその管理下の講師IDが、講座の作成者IDでなければエラー応答
            throw new AuthorizationException('Invalid instructor_id.');
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定された講座がチャプターに関連付けられている講座と一致しない場合はエラー応答
            throw new AuthorizationException('Invalid course_id.');
        }

        // チャプターに紐づく全レッスンIDを取得
        $lessonIds = $chapter->lessons->pluck('id');
        $attendedLessonIds = LessonAttendance::whereIn('lesson_id', $lessonIds)->pluck('lesson_id');

        if ($attendedLessonIds->isNotEmpty()) {
            // 出席のあるレッスンがあれば削除を許可しない
            throw new AuthorizationException('This lessons contains attendance.');
        }

        DB::beginTransaction();

        try {
            // チャプターに紐づく全レッスンを削除
            $chapter->lessons()->delete();

            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            throw $e;
        }
    }
}
