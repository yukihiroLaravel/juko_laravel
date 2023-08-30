<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Attendance;
use App\Model\Course;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StudentIndexRequest;
use App\Http\Resources\Instructor\StudentIndexResource;

class StudentController extends Controller
{
    public function index(StudentIndexRequest $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $attendances = Attendance::with(['student', 'course'])
                                    ->where('course_id', $request->course_id)
                                    ->paginate($perPage, ['*'], 'page', $page);

        $course = Course::find($request->course_id);

        $students = [];

        foreach ($attendances as $attendance) {
            $students[] = [
                'id' => $attendance->student->id,
                'nick_name' => $attendance->student->nick_name,
                'email' => $attendance->student->email,
                'course_title' => $attendance->course->title,
                'attendanced_at' => $attendance->created_at->format('Y/m/d'),
            ];
        }

        return new StudentIndexResource($course, $attendances);

        // return response()->json([
        //     'data' => [
        //         'course' => [
        //             'id' => $course->id,
        //             'image' => $course->image,
        //             'title' => $course->title,
        //         ],
        //         'pagination' => [
        //             'page' => $attendances->currentPage(),
        //             'total' => $attendances->total(),
        //         ],
        //         'students' => $students,
        //     ],
        // ]);
    }
}
