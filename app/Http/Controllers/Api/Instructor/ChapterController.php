<?php

namespace App\Http\Controllers\Api\Instructor;

use Exception;
use App\Model\Course;
use App\Model\Chapter;
use App\Model\Instructor;
use App\Exceptions\ValidationErrorException;
use Illuminate\Http\JsonResponse;
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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\Instructor\ChapterPutStatusRequest;
use App\Http\Requests\Instructor\ChapterPatchStatusRequest;
use App\Http\Requests\Instructor\BulkPatchStatusRequest;

class ChapterController extends Controller
{
    /**
     * チャプター詳細情報を取得
     *
     * @param ChapterShowRequest $request
     * @return ChapterShowResource|JsonResponse
     */
    public function show(ChapterShowRequest $request)
    {
        /** @var Chapter $chapter */
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
     * チャプター新規作成API
     *
     * @param ChapterStoreRequest $request
     * @return JsonResponse
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
                return response()->json([
                    'result' => false,
                    'message' => 'Invalid instructor_id for this course.',
                ], 403);
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
            return response()->json([
                'result' => false
            ], 500);
        }
    }

    /**
     * チャプター更新API
     *
     * @param ChapterPatchRequest $request
     * @return JsonResponse
     */
    public function update(ChapterPatchRequest $request): JsonResponse
    {
        /** @var Instructor $user */
        $user = Instructor::find(Auth::guard('instructor')->user()->id);

        /** @var Chapter $chapter */
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
        ]);
    }

    /**
     * チャプター更新API
     *
     * @param ChapterPatchStatusRequest $request
     * @return JsonResponse
     */
    public function updateStatus(ChapterPatchStatusRequest $request): JsonResponse
    {
        /** @var Chapter $chapter */
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

        return response()->json([
            'result' => true,
        ]);
    }

    /**
     * 複数チャプターの公開/非公開API
     *
     * @param BulkPatchStatusRequest $request
     * @return JsonResponse
     */
    public function bulkPatchStatus(BulkPatchStatusRequest $request): JsonResponse
    {
        try {
            // 認証ユーザー情報取得
            $instructorId = Auth::guard('instructor')->user()->id;

            // 選択されたchapterを取得
            $chapters = Chapter::whereIn('id', $request->chapters)->with('course')->get();

            $chapters->map(function ($chapter) use ($instructorId) {
                // チャプターに紐づく講師でない場合は許可しない
                if ((int) $instructorId !== $chapter->course->instructor_id) {
                    throw new ValidationErrorException('Invalid instructor_id.');
                }
            });

            // 該当するchaptersのidをコレクションで取得
            $chapterIds = $chapters->pluck('id');

            // chaptersのstatusを一括で更新
            Chapter::whereIn('id', $chapterIds)->update([
                'status' => $request->status,
            ]);

            return response()->json([
                'result' => true,
            ]);
        } catch (ValidationErrorException $e) {
            Log::error($e);
            return response()->json([
                'result' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'result' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * チャプター削除API
     *
     * @param ChapterDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(ChapterDeleteRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            /** @var Chapter $chapter */
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
     * @return JsonResponse
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
     * チャプター一括更新API
     *
     * @param ChapterPutStatusRequest $request
     * @return JsonResponse
     */
    public function putStatus(ChapterPutStatusRequest $request): JsonResponse
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
