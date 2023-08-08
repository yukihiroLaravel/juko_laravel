<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonStoreRequest;
use App\Http\Requests\Instructor\LessonSortRequest;
use App\Http\Resources\Instructor\LessonStoreResource;
use App\Model\Lesson;
use App\Model\Instructor;
use App\Model\Chapter;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


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
     * @param  LessonSortRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sort(LessonSortRequest $request)
    {
        DB::beginTransaction();
        
        try {
            $user = Instructor::find(1);
            $lessons = $request->input('lessons');
            foreach ($lessons as $lesson){
                $lessonsSort = Lesson::with('chapter.course')->find($lesson['lesson_id']);
                if($lessonsSort === null){
                    // todo 無い場合は例外に投げる
                }
                if ((int) $request->chapter_id !== $lessonsSort->chapter->id || (int) $request->course_id !== $lessonsSort->chapter->course_id){
                    // todo ここで失敗したら例外に投げる
                }
                Lesson::findOrFail($lesson['lesson_id'])->update([
                    'order' => $lesson['order']
                ]);
            }
            DB::commit();
            return response()->json([
                "result" => true
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
                "result" => false,
            ]);
        }
    }
    
}
