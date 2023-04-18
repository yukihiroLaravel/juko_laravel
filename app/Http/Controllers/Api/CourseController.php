<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseGetRequest;
use App\Http\Requests\CourseStoreRequest;
use App\Http\Requests\CoursesGetRequest;
use App\Http\Requests\Instructor\CourseEditRequest;
use App\Http\Resources\CoursesGetResponse;
use App\Http\Resources\CourseGetResponse;
use App\Http\Resources\Instructor\CourseEditResponse;
use App\Model\Attendance;
use App\Model\Course;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class CourseController extends Controller
{
    /**
     * 講座一覧取得API

     * @param CoursesGetRequest $request
     * @return CoursesGetResponse
     */
    public function index(CoursesGetRequest $request)
    {
        if ($request->text === null) {
            $attendances = Attendance::with(['course.instructor'])->where('student_id', $request->student_id)->get();
            return new CoursesGetResponse($attendances);
        }

        $attendances = Attendance::whereHas('course', function ($q) use ($request) {
            $q->where('title', 'like', "%$request->text%");
        })
            ->with(['course.instructor'])
            ->where('student_id', '=', $request->student_id)
            ->get();
        return new CoursesGetResponse($attendances);
    }

    /**
     * 講座詳細取得API
     *
     * @param CourseGetRequest $request
     * @return CourseGetResponse
     */
    public function show(CourseGetRequest $request)
    {
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'course.instructor',
            'lessonAttendances'
        ])
            ->where('id', $request->attendance_id)
            ->first();

        return new CourseGetResponse($attendance);
    }

    public function edit(CourseEditRequest $course_id)
    {
       $course = Course::find($course_id);  
       return new CourseEditResponse($course);
    }

    /**
     * 講座登録API
     *
     * @param CourseGetRequest $request
     * @return CourseGetResponse
     */
    public function store(CourseStoreRequest $request)
    {
        $file = $request->file('image');
        $extension = $file->getClientOriginalExtension();
        $filename = date('YmdHis') . '.' . $extension;
        $filePath = Storage::putFileAs('courese', $file, $filename);
        Course::insert([
            'instructor_id' => $request->instructor_id,
            'title' => $request->title,
            'image' => $request->image = $filePath,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        return response()->json([
            "result" => true,
        ]);
    }
}
