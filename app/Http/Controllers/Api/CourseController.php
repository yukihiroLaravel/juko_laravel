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
        $file = $request->file('image');

        try {
            //course_idをBDから検索
            $course = Course::FindOrFail($request->course_id);
            //検索した$courseと紐づいているimageをimagePathに格納
            $imagePath = $course->image;

            if (isset($file)){

                // 更新前の画像ファイルを削除
                if (Storage::exists($course->image)) {
                    Storage::delete($course->image);
                }

                // 画像ファイル保存処理
                $extension = $file->getClientOriginalExtension();
                $filename = date('YmdHis').'.'.$extension;
                $imagePath = Storage::putFileAs('course',$file,$filename);
            }

            $course->update([
                'title' => $request->title,
                'image' => $imagePath
            ]);

            return response()->json([
                "result" => true,
                "title" => $request->title,
                "image" => $imagePath
            ]);

        } catch (RuntimeException $e) {
            Log::error($e->getMessage());
            return response()->json([
                "result" => false,
            ], 500);
        }
    }
}
