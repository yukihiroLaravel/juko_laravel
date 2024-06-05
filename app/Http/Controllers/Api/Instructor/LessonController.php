<?php

namespace App\Http\Controllers\Api\Instructor;

use Exception;
use App\Model\Lesson;
use App\Model\Attendance;
use App\Model\Instructor;
use App\Model\LessonAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\ValidationErrorException;
use Illuminate\Auth\Access\AuthorizationException;
use App\Http\Requests\Instructor\LessonSortRequest;
use App\Http\Requests\Instructor\LessonStoreRequest;
use App\Http\Requests\Instructor\LessonDeleteRequest;
use App\Http\Requests\Instructor\LessonUpdateRequest;
use App\Http\Requests\Instructor\LessonPatchStatusRequest;
use App\Http\Requests\Instructor\LessonUpdateTitleRequest;
use App\Http\Requests\Instructor\LessonPutStatusRequest;

class LessonController extends Controller
{
    /**
     * レッスン新規作成API
     *
     * @param  LessonStoreRequest  $request
     * @return JsonResponse
     */
    public function store(LessonStoreRequest $request): JsonResponse
    {
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
     * @param LessonUpdateRequest $request
     * @return JsonResponse
     */
    public function update(LessonUpdateRequest $request): JsonResponse
    {
        $user = Instructor::find($request->user()->id);
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        if ($lesson->chapter->course->instructor_id !== $user->id) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid instructor_id',
            ], 403);
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id || (int) $request->course_id !== $lesson->chapter->course_id) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid chapter_id or course_id.',
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
    public function delete(LessonDeleteRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $lesson = Lesson::with('chapter')->findOrFail($request->lesson_id);

            if (Auth::guard('instructor')->user()->id !== $lesson->chapter->course->instructor_id) {
                return response()->json([
                    'result' => false,
                    'message' => 'Invalid instructor_id.'
                ], 403);
            }

            if ((int) $request->chapter_id !== $lesson->chapter->id) {
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は更新を許可しない
                return response()->json([
                    'result'  => false,
                    'message' => 'Invalid chapter_id.',
                ], 403);
            }

            if (LessonAttendance::where('lesson_id', $lesson->id)->exists()) {
                return response()->json([
                    'result' => false,
                    'message' => 'This lesson has attendance.'
                ], 403);
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
            return response()->json([
                'result' => false,
            ], 500);
        }
    }

    /**
     * レッスンステータス更新API
     *
     * @param LessonPatchStatusRequest $request
     * @return JsonResponse
     */
    public function updateStatus(LessonPatchStatusRequest $request): JsonResponse
    {
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        if (Auth::guard('instructor')->user()->id !== $lesson->chapter->course->instructor_id) {
            return response()->json([
                'result' => false,
                "message" => 'invalid instructor_id.'
            ], 403);
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id) {
            // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は更新を許可しない
            return response()->json([
                'result'  => false,
                'message' => 'Invalid chapter_id.',
            ], 403);
        }

        $lesson->update([
            'status' => $request->status
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * レッスンタイトル変更API
     *
     * @param LessonUpdateTitleRequest $request
     * @return JsonResponse
     */
    public function updateTitle(LessonUpdateTitleRequest $request): JsonResponse
    {
        $user = Auth::guard('instructor')->user();
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        if ($lesson->chapter->course->instructor_id !== $user->id) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid instructor_id',
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
     * レッスン並び替えAPI
     *
     * @param LessonSortRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sort(LessonSortRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            // 認証ユーザー情報取得
            $instructorId = Auth::guard('instructor')->user()->id;

            $courseId = $request->input('course_id');
            $chapterId = $request->input('chapter_id');
            $inputLessons = $request->input('lessons');

            // レッスン一括取得
            $lessons = Lesson::with('chapter.course')->whereIn('id', array_column($inputLessons, 'lesson_id'))->get();

            // 認可
            $lessons->each(function ($lesson) use ($instructorId, $courseId, $chapterId) {
                // 講座に紐づく講師でない場合は許可しない
                if ((int) $instructorId !== $lesson->chapter->course->instructor_id) {
                    throw new ValidationErrorException('Invalid instructor_id.');
                }
                // 指定した講座IDが1レッスンの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $lesson->chapter->course_id) {
                    throw new ValidationErrorException('Invalid course.');
                }
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ((int) $chapterId !== $lesson->chapter_id) {
                    throw new ValidationErrorException('Invalid chapter.');
                }
            });

            // orderカラムを更新（並び替え実施）
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
                'result' => false,
            ]);
        }
    }

    /**
     * 選択済みのレッスンステータス一括更新API
     *
     * @param LessonPutStatusRequest $request
     * @return JsonResponse
     */
    public function putStatus(LessonPutStatusRequest $request): JsonResponse
    {
        // リクエストから必要なデータを取得
        $courseId = $request->input('course_id');
        $chapterId = $request->input('chapter_id');
        $lessonIds = $request->input('lessons');
        $status = $request->input('status');

        // ログイン中のインストラクターを取得
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
                if ($courseId !== $lesson->chapter->course_id) {
                    throw new AuthorizationException('Invalid course_id.');
                }
                // 指定したチャプターIDがレッスンのチャプターIDと一致しない場合は許可しない
                if ($chapterId !== $lesson->chapter_id) {
                    throw new AuthorizationException('Invalid chapter_id.');
                }
            });

            // ステータスを一括更新
            Lesson::whereIn('id', $lessonIds)->update(['status' => $status]);

            return response()->json(['result' => true], 200);
        } catch (AuthorizationException $e) {
            return response()->json([
            'result' => false,
            'message' => $e->getMessage(),
            ], 403);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
            'result' => false,
            'message' => 'An error occurred.',
            ], 500);
        }
    }
}
