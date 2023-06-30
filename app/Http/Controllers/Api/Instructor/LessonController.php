<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonStoreRequest;
use App\Http\Resources\Instructor\LessonStoreResource;
use App\Http\Requests\Instructor\LessonDeleteRequest;
use App\Model\Lesson;
use App\Model\Instructor;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function delete(LessonDeleteRequest $request)
    {

        try {
            $lesson_id = $request->input('lesson_id');
            $lesson = Lesson::with('chapter.course')->findOrFail($lesson_id);

            $course = $lesson->chapter->course;
            $instructor_id = $course->instructor_id;

            //$user = Auth::user();
            $user = Instructor::find(1);

            if ($instructor_id !== $user->id) {
                return response()->json([
                    'result' => false,
                ], 401);
            }

            $lesson->delete();

            return response()->json([
                'result' => true,
            ]);
        } catch (ModelNotFoundException $exception) {
            return response()->json([
                'result' => false,
                'message' => 'Not Found Lesson.'
            ], 404);
        } catch (Exception $exception) {
            return response()->json([
                'result' => false,
            ], 500);
        }
    }
}
