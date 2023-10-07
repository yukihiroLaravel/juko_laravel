<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Instructor;
use App\Model\Course;
use App\Model\Chapter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ChapterDeleteRequest;
use App\Http\Requests\Instructor\ChapterStoreRequest;
use App\Http\Requests\Instructor\ChapterPatchRequest;
use App\Http\Requests\Instructor\ChapterPatchStatusRequest;
use App\Http\Requests\Instructor\ChapterSortRequest;
use App\Http\Requests\Instructor\ChapterShowRequest;
use App\Http\Resources\Instructor\ChapterStoreResource;
use App\Http\Resources\Instructor\ChapterPatchResource;
use App\Http\Resources\Instructor\ChapterShowResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

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
            $chapter = Chapter::create([
                'course_id' => $request->input('course_id'),
                'title' => $request->input('title'),
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
     * @param ChapterGetRequest $request
     * @return ChapterShowResource
     */
    public function show(ChapterShowRequest $request)
    {
        $chapter = Chapter::with('lessons')->findOrFail($request->chapter_id);
        if ((int) $request->course_id !== $chapter->course->id) {
            return response()->json([
                'result' => false,
                'message' => 'invalid course_id.',
            ], 500);
        }

        return response()->json([
            'result' => true,
            'data' => new ChapterShowResource($chapter)
        ]);
    }

    /**
     * チャプター更新API
     *
     * @param ChapterPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ChapterPatchRequest $request)
    {
        $chapter = Chapter::findOrFail($request->chapter_id);
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
        Chapter::findOrFail($request->chapter_id)
            ->update([
                'status' => $request->status
        ]);

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
        $chapter = Chapter::findOrFail($request->chapter_id);
        $chapter->delete();
        return response()->json([
            "result" => true
        ]);
    }

    /**
     * チャプター並び替えAPI
     *
     * @param ChapterSortRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function sort(ChapterSortRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = Instructor::find(1);
            $courseId = $request->input('course_id');
            $chapters = $request->input('chapters');
            $course = Course::findOrFail($courseId);
            if ($user->id !== $course->instructor_id) {
                return response()->json([
                    'result' => false,
                    'message' => 'You are not authorized to perform this action',
                ], 403);
            }
            foreach ($chapters as $chapter) {
                Chapter::where('id',$chapter['chapter_id'])
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
                'message' => 'Not found course.'
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }

    public function putStatus(Request $request)
    {
        Chapter::where('course_id', $request->route('course_id'))
            ->update([
                'status' => $request->status
            ]);

        return response()->json([
            'result' => 'true'
        ]);
    }
}
