<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Chapter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\ChapterDeleteRequest;
use App\Http\Requests\Instructor\ChapterStoreRequest;
use App\Http\Requests\Instructor\ChapterPatchRequest;
use App\Http\Requests\Instructor\ChapterSortRequest;
use App\Http\Resources\Instructor\ChapterStoreResource;
use App\Http\Resources\Instructor\ChapterPatchResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

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
        try {
            DB::beginTransaction();

            $courseId = $request->input('course_id');
            $chapters = $request->input('chapters');

            foreach ($chapters as $chapter) {
                Chapter::where('id',$chapter['chapter_id'])->where('course_id', $courseId)->firstOrFail()
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
}