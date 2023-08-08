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
use App\Model\Chapter;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $user = Instructor::find(1);
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
     * @param  LessonSortRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sort(LessonSortRequest $request)
    {
        $lesson_sort = new Chapter;

        if ((int) $request->chapter->id == $lesson_sort->chapter_id || (int) $request->course->id == $lesson->course_id){

            DB::beginTransaction();
            try {
                $lessons = $request->input('lessons');
                foreach ($lessons as $lesson) {
                    Lesson::findOrFail($lesson['lesson_id'])->update([
                        'order' => $lesson['order']
                    ]);
                }

                DB::commit();


        DB::beginTransaction();

        try {
            $user = Chapter::find(1);
            $lesson = Lesson::with('chapter.course')->findOrFail($request->lesson_id);

            if ((int) $request->chapter_id !== $lesson->chapter->id || (int) $request->course_id !== $lesson->chapter->course_id){
                return response()->json([
                    "result" => false,
                    "message"=> "Invalid chapter_id or course_id.",
                ]);
            }
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

            //$user = Auth::user();
            $user = Instructor::find(1);

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
