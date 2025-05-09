<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Exceptions\ValidationErrorException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\Lesson\BulkDeleteRequest;
use App\Http\Requests\Instructor\Lesson\DeleteAllRequest;
use App\Http\Requests\Instructor\Lesson\DeleteRequest;
use App\Http\Requests\Instructor\Lesson\PutRequest;
use App\Http\Requests\Instructor\Lesson\PutStatusRequest;
use App\Http\Requests\Instructor\Lesson\SortRequest;
use App\Http\Requests\Instructor\Lesson\StoreRequest;
use Illuminate\Http\Request;
use App\Http\Requests\Instructor\Lesson\UpdateStatusRequest;
use App\Http\Requests\Instructor\Lesson\UpdateTitleRequest;
use App\services\Lesson\UpdateLessonStatusService;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @tags Instructor-Lesson
 */
class LessonController extends Controller
{
    /**
     * レッスン新規作成API
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $maxOrder = Lesson::where('chapter_id', $request->chapter_id)->max('order');
        $course = Course::findOrFail($request->course_id);
        if ($course->instructor_id !== $request->user()->id) {
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        DB::beginTransaction();
        try {
            $lesson = Lesson::create([
                'chapter_id' => $request->chapter_id,
                'title' => $request->title,
                'status' => Lesson::STATUS_PRIVATE,
                'order' => (int) $maxOrder + 1,
            ]);

            $attendances = Attendance::where('course_id', $request->course_id)->get();
            $lesson_id = $lesson->id;
            $attendances->each(function ($attendance) use (&$lesson_id) {
                LessonAttendance::create([
                    'attendance_id' => $attendance->id,
                    'lesson_id' => $lesson_id,
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
     */
    public function put(PutRequest $request): JsonResponse
    {
        $user = Instructor::find($request->user()->id);
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);
        assert($lesson instanceof Lesson);

        if ($lesson->chapter->course->instructor_id !== $user->id) {
            throw new AuthorizationException('Forbidden, invalid instructor_id.');
        }

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            throw new AuthorizationException('Forbidden, invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            throw new AuthorizationException('Forbidden, invalid chapter_id.');
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
     */
    public function delete(DeleteRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $lesson = Lesson::with('chapter')->findOrFail($request->lesson_id);

            if (Auth::guard('instructor')->user()->id !== $lesson->chapter->course->instructor_id) {
                throw new AuthorizationException('Forbidden, invalid instructor_id.');
            }

            if ((int) $request->chapter_id !== $lesson->chapter->id) {
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は更新を許可しない
                throw new AuthorizationException('Invalid chapter_id.');
            }

            if (LessonAttendance::where('lesson_id', $lesson->id)->exists()) {
                throw new AuthorizationException('Forbidden, this lesson has attendance.');
            }

            // 削除対象レッスンのorderカラムを0に設定する
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
     * 複数のレッスン削除API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkDelete(BulkDeleteRequest $request)
    {
        // ログイン中の講師IDを取得
        $instructorId = Auth::guard('instructor')->user()->id;
        // リクエストからデータを取得
        $courseId = $request->input('course_id');
        $chapterId = $request->input('chapter_id');
        $lessonIds = $request->input('lessons');

        try {
            // レッスン情報を取得
            $lessons = Lesson::with('chapter.course')->whereIn('id', $lessonIds)->get();

            $lessons->each(function (Lesson $lesson) use ($instructorId, $chapterId, $courseId) {
                // 自身の講座・チャプターに紐づくレッスンでない場合は許可しない
                if ((int) $instructorId !== $lesson->chapter->course->instructor_id) {
                    throw new ValidationErrorException('Invalid instructor_id.');
                }
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ((int) $chapterId !== $lesson->chapter_id) {
                    throw new ValidationErrorException('Invalid chapter.');
                }
                // 指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $lesson->chapter->course_id) {
                    throw new ValidationErrorException('Invalid course.');
                }
                // 受講情報が登録されている場合は許可しない
                if ($lesson->lessonAttendances->isNotEmpty()) {
                    throw new ValidationErrorException('This lesson has attendance.');
                }
            });

            DB::beginTransaction();

            // 削除対象レッスンのorderカラムを0に設定する
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
     * レッスンステータス更新API
     */
    public function updateStatus(Request $request, UpdateLessonStatusService $updateLessonStatusService): JsonResponse
    {
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        if (Auth::guard('instructor')->user()->id !== $lesson->chapter->course->instructor_id) {
            throw new AuthorizationException('invalid instructor_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は更新を許可しない
            throw new AuthorizationException('Invalid chapter_id.');
        }

        // サービスの呼び出し（関数のように使える）
        $updateLessonStatusService($lesson, $request->status);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * レッスンタイトル変更API
     */
    public function updateTitle(UpdateTitleRequest $request): JsonResponse
    {
        $user = Auth::guard('instructor')->user();
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        if ($lesson->chapter->course->instructor_id !== $user->id) {
            throw new AuthorizationException('Invalid instructor_id.');
        }

        if ((int) $request->course_id !== $lesson->chapter->course_id) {
            throw new AuthorizationException('Invalid course_id.');
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            throw new AuthorizationException('Invalid chapter_id.');
        }

        $lesson->update([
            'title' => $request->title,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * チャプターに紐づく全レッスンを削除するAPI
     */
    public function deleteAll(DeleteAllRequest $request): JsonResponse
    {

        // チャプターを取得
        /** @var Chapter $chapter */
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        // 現在の講師がチャプターの講座の作成者であるか確認
        if (Auth::guard('instructor')->user()->id !== $chapter->course->instructor_id) {
            throw new AuthorizationException('Invalid instructor_id.');
        }

        // 指定された course_id がチャプターに関連付けられている course_id と一致するか確認
        if ((int) $request->course_id !== $chapter->course->id) {
            throw new AuthorizationException('Invalid course_id.');
        }

        // チャプターに紐づく全レッスンIDを取得
        $lessonIds = $chapter->lessons->pluck('id');
        $attendedLessonIds = LessonAttendance::whereIn('lesson_id', $lessonIds)->pluck('lesson_id');
        if ($attendedLessonIds->isNotEmpty()) {
            // 出席のあるレッスンがあれば削除を許可しない
            throw new AuthorizationException('This lessons contains attendance.');
        }

        // 認可チェックをパスした後にトランザクションを開始
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
            Log::error($e);
            throw $e;
        }
    }

    /**
     * レッスン並び替えAPI
     */
    public function sort(SortRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $instructorId = Auth::guard('instructor')->user()->id;

            $courseId = $request->input('course_id');
            $chapterId = $request->input('chapter_id');
            $inputLessons = $request->input('lessons');

            // レッスン一括取得
            $lessons = Lesson::with('chapter.course')->whereIn('id', array_column($inputLessons, 'lesson_id'))->get();

            // 認可
            $lessons->each(function (Lesson $lesson) use ($instructorId, $courseId, $chapterId) {
                // 講座に紐づく講師でない場合は許可しない
                if ((int) $instructorId !== $lesson->chapter->course->instructor_id) {
                    throw new AuthorizationException('Forbidden, invalid instructor.');
                }
                // 指定した講座IDが1レッスンの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $lesson->chapter->course_id) {
                    throw new AuthorizationException('Forbidden, invalid course.');
                }
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ((int) $chapterId !== $lesson->chapter_id) {
                    throw new AuthorizationException('Forbidden, invalid chapter.');
                }
            });

            // orderカラムを更新（並び替え実施）
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
     * 選択済みのレッスンステータス一括更新API
     */
    public function putStatus(PutStatusRequest $request): JsonResponse
    {
        // リクエストから必要なデータを取得
        $courseId = $request->input('course_id');
        $chapterId = $request->input('chapter_id');
        $lessonIds = $request->input('lessons');
        $status = $request->input('status');
        // ログイン中の講師IDを取得
        $instructorId = Auth::guard('instructor')->user()->id;
        // クエリ実行
        $lessons = Lesson::with('chapter.course')->whereIn('id', $lessonIds)->get();
        try {
            // 認可
            $lessons->each(function (Lesson $lesson) use ($instructorId, $chapterId, $courseId) {
                // 講座に紐づく講師でない場合は許可しない
                if ($instructorId !== $lesson->chapter->course->instructor_id) {
                    throw new AuthorizationException('Invalid instructor_id.');
                }
                // 指定した講座IDがレッスンの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $lesson->chapter->course_id) {
                    throw new AuthorizationException('Invalid course_id.');
                }
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ((int) $chapterId !== $lesson->chapter_id) {
                    throw new AuthorizationException('Invalid chapter_id.');
                }
            });
            // ステータスを一括更新
            Lesson::whereIn('id', $lessonIds)->update(['status' => $status]);

            return response()->json([
                'result' => true,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'result' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
