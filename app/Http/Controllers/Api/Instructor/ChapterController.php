<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ChapterBulkDeleteRequest;
use App\Http\Requests\Instructor\ChapterDeleteAllRequest;
use App\Http\Requests\Instructor\ChapterDeleteRequest;
use App\Http\Requests\Instructor\ChapterPatchRequest;
use App\Http\Requests\Instructor\ChapterPatchStatusRequest;
use App\Http\Requests\Instructor\ChapterPutStatusRequest;
use App\Http\Requests\Instructor\ChapterShowRequest;
use App\Http\Requests\Instructor\ChapterSortRequest;
use App\Http\Requests\Instructor\ChapterStoreRequest;
use App\Http\Requests\Instructor\ChapterUpdateStatusRequest;
use App\Http\Resources\Instructor\ChapterShowResource;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChapterController extends Controller
{
    /**
     * チャプター詳細情報を取得
     *
     * @return ChapterShowResource|JsonResponse
     */
    public function show(ChapterShowRequest $request)
    {
        // チャプターを取得
        $chapter = Chapter::with(['lessons', 'course'])->findOrFail($request->chapter_id);

        if (Auth::guard('instructor')->user()->id !== $chapter->course->instructor_id) {
            // ログインしている講師が作成していないチャプターの更新を許可しない
            throw new AuthorizationException('Invalid instructor_id.');
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座IDがチャプターの講座IDと一致しない場合は更新を許可しない
            throw new AuthorizationException('Invalid course_id.');
        }

        return new ChapterShowResource($chapter);
    }

    /**
     * チャプター新規作成API
     */
    public function store(ChapterStoreRequest $request): JsonResponse
    {
        try {
            // 講師の情報を取得
            /** @var Instructor $user */
            $user = Auth::guard('instructor')->user();

            // 講座を取得
            /** @var Course $course */
            $course = Course::with('chapters')->findOrFail($request->input('course_id'));

            if ($course->instructor_id !== $user->id) {
                // 講座の作成者が現在の講師と一致しない場合はエラーを返す
                throw new AuthorizationException('Invalid instructor_id for this course.');
            }

            $order = $course->chapters->count();
            $newOrder = $order + 1;
            Chapter::create([
                'course_id' => $course->id,
                'title' => $request->input('title'),
                'order' => $newOrder,
                'status' => Chapter::STATUS_PUBLIC,
            ]);

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            Log::error($e);

            throw $e;
        }
    }

    /**
     * チャプター更新API
     */
    public function update(ChapterPatchRequest $request): JsonResponse
    {
        /** @var Instructor $user */
        $user = Instructor::find(Auth::guard('instructor')->user()->id);

        /** @var Chapter $chapter */
        $chapter = Chapter::findOrFail($request->chapter_id);

        if ($chapter->course->instructor_id !== $user->id) {
            // ログインしている講師が作成していないチャプターの更新を許可しない
            throw new AuthorizationException('Invalid instructor_id.');
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座IDがチャプターの講座IDと一致しない場合は更新を許可しない
            throw new AuthorizationException('Invalid course_id.');
        }

        $chapter->update([
            'title' => $request->title,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * チャプター更新API
     * TODO このメソッドは削除予定
     */
    public function updateStatus(ChapterUpdateStatusRequest $request): JsonResponse
    {
        /** @var Chapter $chapter */
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        if (Auth::guard('instructor')->user()->id !== $chapter->course->instructor_id) {
            // ログインしている講師が作成していないチャプターの更新を許可しない
            throw new AuthorizationException('invalid instructor_id.');
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座IDがチャプターの講座IDと一致しない場合は更新を許可しない
            throw new AuthorizationException('Invalid course_id.');
        }

        $chapter->update([
            'status' => $request->status,
        ]);

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * チャプターの公開/非公開API
     */
    public function patchStatus(ChapterPatchStatusRequest $request): JsonResponse
    {
        try {
            // リクエストで送られたcourseとchapterのidを変数に格納
            $courseId = $request->course_id;

            // 認証ユーザー情報取得
            $instructorId = Auth::guard('instructor')->user()->id;

            // 選択されたチャプターを取得
            $chapters = Chapter::whereIn('id', $request->chapters)->with('course')->get();

            // バリデーション
            $chapters->each(function (Chapter $chapter) use ($instructorId, $courseId) {
                // チャプターに紐づく講師でない場合は許可しない
                if ((int) $instructorId !== $chapter->course->instructor_id) {
                    throw new AuthorizationException('Forbidden, invalid instructor_id.');
                }
                // チャプターに紐づく講座IDがリクエストの講座IDと一致しない場合は許可しない
                if ((int) $courseId !== $chapter->course_id) {
                    throw new AuthorizationException('Forbidden, invalid course_id.');
                }
            });

            // チャプターの状態を一括で更新
            Chapter::whereIn('id', $chapters->pluck('id'))->update([
                'status' => $request->status,
            ]);

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 選択済チャプターの削除API
     */
    public function bulkDelete(ChapterBulkDeleteRequest $request): JsonResponse
    {
        // 認証ユーザー情報取得
        $instructorId = Auth::guard('instructor')->user()->id;

        $chapterIds = $request->input('chapters', []);
        $courseId = $request->input('course_id');

        try {
            $chapters = Chapter::with(['course', 'lessons'])->whereIn('id', $chapterIds)->get();
            $chapters->each(function (Chapter $chapter) use ($instructorId, $courseId) {
                if ((int) $instructorId !== $chapter->course->instructor_id) {
                    // チャプターに紐づく講師でない場合は許可しない
                    throw new AuthorizationException('Forbidden, invalid instructor_id.');
                }
                if ((int) $courseId !== $chapter->course_id) {
                    // チャプターに紐づく講座IDがリクエストの講座IDと一致しない場合は許可しない
                    throw new AuthorizationException('Forbidden, invalid course_id.');
                }
            });

            $lessonIds = $chapters->pluck('lessons.*.id')->flatten();
            if (LessonAttendance::whereIn('lesson_id', $lessonIds)->exists()) {
                // 受講中のレッスンがあれば、エラー応答
                throw new AuthorizationException('Forbidden, this lesson has attendance.');
            }

            // チャプターを一括で削除
            Chapter::whereIn('id', $chapters->pluck('id'))->delete();

            // TODO レッスンも削除する必要がある。
            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }

    /**
     * チャプター削除API
     * TODO このメソッドは削除予定
     */
    public function delete(ChapterDeleteRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            /** @var Chapter $chapter */
            $chapter = Chapter::with('course', 'lessons')->findOrFail($request->chapter_id);

            if (Auth::guard('instructor')->user()->id !== $chapter->course->instructor_id) {
                // ログインしている講師が作成していないチャプターの更新を許可しない
                throw new AuthorizationException('Invalid instructor_id.');
            }

            if ((int) $request->course_id !== $chapter->course->id) {
                // 指定した講座IDがチャプターの講座IDと一致しない場合は更新を許可しない
                throw new AuthorizationException('Invalid course_id.');
            }

            //受講中のチャプターは削除できないようにする
            $lessonIds = $chapter->lessons->pluck('id')->toArray();
            if (LessonAttendance::whereIn('lesson_id', $lessonIds)->exists()) {
                throw new AuthorizationException('This chapter contains attendance.');
            }

            // 削除対象チャプターのorderカラムを0に設定する
            $chapter->update(['order' => 0]);

            $chapter->delete();

            Chapter::where('course_id', $chapter->course_id)
                ->orderBy('order')
                ->get()
                ->each(function ($chapter, $index) {
                    $chapter->update(['order' => $index + 1]);
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
     * 全チャプター削除API
     */
    public function deleteAll(ChapterDeleteAllRequest $request): JsonResponse
    {
        $courseId = $request->input('course_id');

        DB::beginTransaction();
        try {

            //コースに紐づくチャプター情報とレッスン情報を取得
            $course = Course::with('chapters.lessons')->find($courseId);
            $chapterIds = $course->chapters->pluck('id')->toArray();

            // ログイン中の講師の講座のチャプターでなければエラー応答
            if (Auth::guard('instructor')->user()->id !== $course->instructor_id) {
                throw new AuthorizationException('Invalid instructor_id.');
            }

            // チャプターに紐づく全レッスンIDを取得
            $lessonIds = $course->chapters->pluck('lessons')->flatten()->pluck('id')->toArray();
            if (LessonAttendance::whereIn('lesson_id', $lessonIds)->exists()) {
                // 受講中のレッスンがあれば、エラー応答
                throw new AuthorizationException('This lesson has attendance.');
            }

            // チャプターを削除
            Chapter::where('course_id', $courseId)->delete();
            Lesson::whereIn('chapter_id', $chapterIds)->delete();

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
     * チャプター並び替えAPI
     */
    public function sort(ChapterSortRequest $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $user = Instructor::find(Auth::guard('instructor')->user()->id);
            $courseId = $request->input('course_id');
            $chapters = $request->input('chapters');
            $course = Course::findOrFail($courseId);

            if ($user->id !== $course->instructor_id) {
                // 講座の作成者が現在の講師と一致しない場合はエラーを返す
                throw new AuthorizationException('Invalid instructor_id.');
            }

            foreach ($chapters as $chapter) {
                Chapter::where('id', $chapter['chapter_id'])
                    ->where('course_id', $courseId)
                    ->firstOrFail()
                    ->update([
                        'order' => $chapter['order'],
                    ]);
            }

            DB::commit();

            return response()->json([
                'result' => true,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            throw new AuthorizationException('Not found.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * チャプター一括更新API
     */
    public function putStatus(ChapterPutStatusRequest $request): JsonResponse
    {
        /** @var Course $course */
        $course = Course::findOrFail($request->course_id);

        if (Auth::guard('instructor')->user()->id !== $course->instructor_id) {
            // ログインしていない講師の更新を許可しない
            throw new AuthorizationException('Not authorized.');
        }

        Chapter::chapterUpdateAll($request->course_id, $request->status);

        return response()->json([
            'result' => true,
        ]);
    }
}
