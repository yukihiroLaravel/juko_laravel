<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonStoreRequest;
use App\Http\Requests\Instructor\LessonSortRequest;
use App\Http\Resources\Instructor\LessonStoreResource;
use App\Http\Requests\Instructor\LessonDeleteRequest;
use App\Http\Requests\Instructor\LessonUpdateRequest;
use App\Http\Resources\Instructor\LessonUpdateResource;
use App\Model\Lesson;
use App\Model\Instructor;
use App\Model\LessonAttendance;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
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
        $maxOrder = Lesson::where('chapter_id', $request->chapter_id)->max('order');

        try {
            $lesson = Lesson::create([
                'chapter_id' => $request->chapter_id,
                'title' => $request->title,
                'status' => Lesson::STATUS_PRIVATE,
                'order' => (int) $maxOrder + 1,
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
        $user = Instructor::find($request->user()->id);
        $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

        if ($lesson->chapter->course->instructor_id !== $user->id) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid instructor_id',
            ], 403);
        }

        if ((int) $request->chapter_id !== $lesson->chapter->id || (int) $request->course_id !== $lesson->chapter->course_id) {
            return response()->json([
                'result' => false,
                'message' => 'Invalid chapter_id or course_id.',
            ], 403);
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
    }

    /**
     * レッスン並び替えAPI
     *
     * @param  LessonSortRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sort(LessonSortRequest $request)
    {
        DB::beginTransaction();

        try {
            $user = Instructor::find($request->user()->id);

            $inputLessons = $request->input('lessons');
            foreach ($inputLessons as $inputLesson) {
                $lesson = Lesson::with('chapter.course')->findOrFail($inputLesson['lesson_id']);

                // 講師idが一致するか
                if ($user->id !== $lesson->chapter->course->instructor_id) {
                    throw new Exception('Invalid instructor.');
                }

                if (
                    (int) $request->chapter_id !== $lesson->chapter->id ||
                    (int) $request->course_id !== $lesson->chapter->course_id
                ) {
                    throw new Exception('Invalid lesson.');
                }

                $lesson->update([
                    'order' => $inputLesson['order']
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

    /**
     * レッスン削除API
     *
     * @param LessonDeleteRequest $request
     * @return JsonResponse
     */
    public function delete(LessonDeleteRequest $request)
    {

        try {
            $lessonId = $request->input('lesson_id');
            $lesson = Lesson::with('chapter.course')->findOrFail($lessonId);

            $course = $lesson->chapter->course;
            $instructorId = $course->instructor_id;

            $user = Instructor::find($request->user()->id);

            if ($instructorId !== $user->id) {
                return response()->json([
                    'result' => false,
                    'message' => 'Invalid instructor_id.'
                ], 403);
            }

            if (LessonAttendance::where('lesson_id', $lesson->id)->exists()) {
                return response()->json([
                    'result' => false,
                    'message' => 'Information about current lessons'
                ], 403);
            }

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
