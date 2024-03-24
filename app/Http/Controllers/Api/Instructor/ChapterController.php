<?php

namespace App\Http\Controllers\Api\Instructor;

use Exception;
use App\Model\Course;
use App\Model\Chapter;
use App\Model\Lesson;
use App\Model\Instructor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Instructor\ChapterShowRequest;
use App\Http\Requests\Instructor\ChapterSortRequest;
use App\Http\Requests\Instructor\ChapterPatchRequest;
use App\Http\Requests\Instructor\ChapterStoreRequest;
use App\Http\Requests\Instructor\ChapterDeleteRequest;
use App\Http\Resources\Instructor\ChapterShowResource;
use App\Http\Resources\Instructor\ChapterPatchResource;
use App\Http\Resources\Instructor\ChapterStoreResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\Instructor\ChapterPutStatusRequest;
use App\Http\Requests\Instructor\ChapterPatchStatusRequest;

class ChapterController extends Controller
{
    /**
     * チャプター新規作成API
     *
     * @param ChapterStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ChapterStoreRequest $request)
    {
        try {
            // 講師の情報を取得
            $user = Auth::guard('instructor')->user();

            // リクエストに含まれる講座IDを使用して対応する講座を取得
            $course = Course::with('chapters')->findOrFail($request->input('course_id'));

            // 講座の作成者が現在の講師であるかどうかを確認
            if ($course->instructor_id !== $user->id) {
                // 講座の作成者が現在の講師と一致しない場合はエラーを返す
                return response()->json([
                    'result' => false,
                    'message' => 'Invalid instructor_id for this course.',
                ], 403);
            }

            $order = $course->chapters->count();
            $newOrder = $order + 1;
            $chapter = Chapter::create([
                'course_id' => $course->id,
                'title' => $request->input('title'),
                'order' => $newOrder,
                'status' => Chapter::STATUS_PUBLIC,
            ]);

            return response()->json([
                'result' => true,
                'data' => new ChapterStoreResource($chapter),
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'result' => false
            ], 500);
        }
    }

    /**
     * チャプター詳細情報を取得
     *
     * @param ChapterShowRequest $request
     * @return ChapterShowResource|\Illuminate\Http\JsonResponse
     */
    public function show(ChapterShowRequest $request)
    {
        $chapter = Chapter::with(['lessons','course'])->findOrFail($request->chapter_id);

        if ((int) $request->course_id !== $chapter->course->id) {
            return response()->json([
                'message' => 'Invalid course_id.',
            ], 403);
        }

        if (Auth::guard('instructor')->user()->id !== $chapter->course->instructor_id) {
            return response()->json([
                'message' => 'Invalid instructor_id.',
            ], 403);
        }

        return new ChapterShowResource($chapter);
    }

    /**
     * チャプター更新API
     *
     * @param ChapterPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ChapterPatchRequest $request)
    {
        $user = Instructor::find($request->user()->id);
        $chapter = Chapter::findOrFail($request->chapter_id);
        if ($chapter->course->instructor_id !== $user->id) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid instructor_id',
            ], 403);
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座IDがチャプターの講座IDと一致しない場合は更新を許可しない
            return response()->json([
                'result'  => false,
                'message' => 'Invalid course_id.',
            ], 403);
        }

        $chapter->update([
            'title' => $request->title
        ]);

        return response()->json([
            'result' => true,
            'data' => new ChapterPatchResource($chapter),
        ]);
    }

    /**
     * チャプター更新API(公開・非公開切り替え)
     *
     * @param ChapterPatchStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(ChapterPatchStatusRequest $request)
    {
        $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

        if (Auth::guard('instructor')->user()->id !== $chapter->course->instructor_id) {
            return response()->json([
                'result' => false,
                "message" => 'invalid instructor_id.'
            ], 403);
        }

        if ((int) $request->course_id !== $chapter->course->id) {
            // 指定した講座IDがチャプターの講座IDと一致しない場合は更新を許可しない
            return response()->json([
                'result'  => false,
                'message' => 'Invalid course_id.',
            ], 403);
        }

        $chapter->update([
            'status' => $request->status
        ]);

        // チャプターを非公開にする場合は、レッスンのステータスも更新する
        if ($request->status === Chapter::STATUS_PRIVATE ) {
            Lesson::where('chapter_id', $request->chapter_id)
            ->update([
                'status' => $request->status
            ]);
        }

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * チャプター削除API
     *
     * @param ChapterDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(ChapterDeleteRequest $request)
    {
        DB::beginTransaction();

        try {
            $chapter = Chapter::with('course')->findOrFail($request->chapter_id);

            if (Auth::guard('instructor')->user()->id !== $chapter->course->instructor_id) {
                return response()->json([
                    'result' => false,
                    'message' => 'Invalid instructor_id.'
                ], 403);
            }

            if ((int) $request->course_id !== $chapter->course->id) {
                // 指定した講座IDがチャプターの講座IDと一致しない場合は更新を許可しない
                return response()->json([
                    'result'  => false,
                    'message' => 'Invalid course_id.',
                ], 403);
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
                "result" => true
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
     * チャプター並び替えAPI
     *
     * @param ChapterSortRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sort(ChapterSortRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = Instructor::find($request->user()->id);
            $courseId = $request->input('course_id');
            $chapters = $request->input('chapters');
            $course = Course::findOrFail($courseId);

            if ($user->id !== $course->instructor_id) {
                return response()->json([
                    'result' => false,
                    'message' => 'Invalid instructor_id.',
                ], 403);
            }

            foreach ($chapters as $chapter) {
                Chapter::where('id', $chapter['chapter_id'])
                ->where('course_id', $courseId)
                ->firstOrFail()
                ->update([
                    'order' => $chapter['order']
                ]);
            }

            DB::commit();
            return response()->json([
                'result' => true,
            ]);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'result' => false,
                'message' => 'Not found.',
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }

    /**
     * チャプター一括更新API(公開・非公開切り替え)
     *
     * @param ChapterPutStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function putStatus(ChapterPutStatusRequest $request)
    {
        /** @var Course $course */
        $course = Course::findOrFail($request->course_id);

        if (Auth::guard('instructor')->user()->id !== $course->instructor_id) {
            return response()->json([
                'result' => false,
                "message" => "Not authorized."
            ], 403);
        }

        Chapter::chapterUpdateAll($request->course_id, $request->status);

        return response()->json([
            'result' => true,
        ]);
    }
}
