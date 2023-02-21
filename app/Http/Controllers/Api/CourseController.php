<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseGetRequest;
use App\Http\Requests\CoursesGetRequest;
use App\Http\Resources\CoursesGetResponse;
use App\Http\Resources\CourseGetResponse;
use App\Model\Attendance;

class CourseController extends Controller
{
    /**
     * 講座一覧取得API
     *
     * @param CoursesGetRequest $request
     * @return CoursesGetResponse
     */
    public function index(CoursesGetRequest $request)
    {
        if($request->text === null){
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

        //テキストがなかったら全件取得
        //ToDo 最終的にurlを一緒にするので上のindexメソッドと一緒に実行できるようにする

        // return response()->json([
        //     "searchedCourseAttendance" => $searchedCourseAttendance //変数名。キー名が何を示しているかを分かるようにする。
        // ]);

    }

    /**
     * 講座検索取得API
     *
     * @param CoursesGetRequest $request
     * @return CoursesGetResponse
     */
    public function search(CoursesGetRequest $request)
    {

        $searchedCourseAttendance = Attendance::whereHas('course', function ($q) use ($request) {
            $q->where('title', 'like', "%$request->text%");
        })
            ->where('student_id', '=', $request->student_id)
            ->get();

        //テキストがなかったら全件取得
        //ToDo 最終的にurlを一緒にするので上のindexメソッドと一緒に実行できるようにする

        return response()->json([
            "searchedCourseAttendance" => $searchedCourseAttendance //変数名。キー名が何を示しているかを分かるようにする。
        ]);
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
}
