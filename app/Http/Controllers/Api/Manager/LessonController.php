<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\LessonSortRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Model\Lesson;
use App\Model\Instructor;
use Exception;


class LessonController extends Controller
{
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
}