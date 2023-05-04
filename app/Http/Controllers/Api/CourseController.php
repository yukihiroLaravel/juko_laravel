<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseGetRequest;
use App\Http\Requests\CoursesGetRequest;
use App\Http\Requests\CoursePatchRequest;
use App\Http\Resources\CoursesGetResponse;
use App\Http\Resources\CourseGetResponse;
use App\Http\Resources\CoursePatchResponse;
use App\Model\Attendance;
use App\Model\Course;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;


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

    public function update(CoursePatchRequest $request)
    {
        try{
            //リクエストを通ったimageを格納する
            $file = $request->file('image');
            //拡張子の指定
            $extension = $file->getClientOriginalExtension();
            //ファイルに名前をつけて、最後に拡張子を接続している
            $filename = date('YmdHis').'.'.$extension;
            //ファイルパスに保存する
            $filePath = Storage::putFileAs('course',$file,$filename);
            $course = Course::FindOrFail($request->course_id);
            $course->title = $request->title;

            if (isset($file)){
                if (Storage::exists($course->image))
                {
                    Storage::delete($course->image);
                    $course->update([
                        "title" => $request->title,
                        "image" => $filePath
                    ]);
                } else {
                    $course->update([
                        "title" => $request->title,
                    ]);
                    
                }
            }
            return response()->json([
                "result" => 200,
                "title" => $request->title,
                "image" => $filePath
            ]);

        } catch (\RuntimeException $e) {
            Log::error($e->getMessage());
            return response()->json([
                "result" => false,
                "error_message" => "Invalid Request Body.",
                "error_code" => "400"
            ]);
        }
    }
}
