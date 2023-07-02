<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonStoreRequest;
use App\Http\Resources\Instructor\LessonStoreResource;
use App\Http\Requests\Instructor\LessonUpdateRequest;
use App\Http\Resources\Instructor\LessonUpdateResource;
use App\Model\Lesson;
use App\Model\Instructor;
use App\Model\Course;
use Exception;
use Illuminate\Support\Facades\Log;


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
                'status' => Lesson::STATUS_PRIVATE,
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

    public function update(LessonUpdateRequest $request)
    {
        $user = Instructor::find(2);
        $lesson = Lesson::with('chapter')->findOrFail($request->lesson_id);
        // dd($lesson);
        $course = Course::where('id' , $lesson->chapter->course_id)->first();
        // dd($lesson->course_id);
        if ($course->instructor_id == $user->id) {
          
                    if ((int) $request->chapter_id !== $lesson->chapter->id || (int) $request->course_id !== $lesson->chapter->course_id) {
                        return response()->json([
                            'result' => false,
                            'message' => 'invalid chapter_id or course_id.',
                        ], 500);
                    }
            
                    $lesson->update([
                        'title' => $request->title,
                        'url' => $request->url,
                        'remarks' => $request->remarks,
                        'status' => $request->status,
                    ]);
            
                    return response()->json([
                        'result' => true,
                        'data' => new LessonUpdateResource($lesson->refresh())
                    ]);
        }else{
            return response()->json([
                'result' => false,
                'message' => 'invalid chapter_id or course_id. 仮認証2',
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
}
