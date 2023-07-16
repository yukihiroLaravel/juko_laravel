<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonStoreRequest;
use App\Http\Requests\Instructor\LessonSortRequest;
use App\Http\Resources\Instructor\LessonStoreResource;
use App\Model\Lesson;
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
            $lessons = new Chapter;
            if ((int) $request->chapter->id == $lessons->chapter_id || (int) $request->course->id == $lessons->course_id){
                return response()->json([
                    "result" => false
                ]);
            }

            $lessons = $request->input('lessons');
            foreach ($lessons as $lesson) {
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
