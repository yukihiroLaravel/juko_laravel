<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonStoreRequest;
use App\Http\Resources\Instructor\LessonStoreResource;
use App\Model\Lesson;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    /**
     * レッスン新規作成API
     *
     * @param  LessonStoreRequest  $request
     * @return LessonStoreResource
     */
    public function store(LessonStoreRequest $request)
    {
        try {
            $lesson = Lesson::create([
                'chapter_id' => $request->input('chapter_id'),
                'title' => $request->input('title'),
                'status' =>  Lesson::STATUS_PRIVATE,
            ]);

            return response()->json([
                "result" => true,
                "data" => new LessonStoreResource($lesson),
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);
        }
    }

    /**
     * レッスン並び替えAPI
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sort()
    {
        return response()->json([]);
    }

    /**
     * レッスン削除API
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function delete($request)
    {
        return response()->json($request);
        //try {

        $lesson = Lesson::findOrFail($lesson_id);

        //$lesson->lesson_attendances()->delete();

        //$lesson->delete();

        return response()->json([
            'success' => true,
            'message' => 'レッスンが正常に削除されました。',
        ]);
        //} catch (ModelNotFoundException $exception) {
        return response()->json([
            'success' => false,
            'message' => '指定されたレッスンが見つかりませんでした。',
        ], 404);
        //} catch (Exception $exception) {
        return response()->json([
            'success' => false,
            'message' => '削除中にエラーが発生しました。',
        ], 500);
        //}
    }
}
