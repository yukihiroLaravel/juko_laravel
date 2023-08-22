<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;    //postman確認のため仮作成
use App\Http\Requests\CourseShowRequest;
use App\Http\Requests\CourseIndexRequest;
use App\Http\Resources\CourseIndexResource;
use App\Http\Resources\CourseShowResource;
use App\Model\Attendance;
use App\Model\Course;

class CourseController extends Controller
{
    /**
     * 講座一覧取得API
     *
     * @param CourseIndexRequest $request
     * @return CourseIndexResource
     */
    public function index(CourseIndexRequest $request)
    {
        if ($request->text === null) {
            $attendances = Attendance::with(['course.instructor'])->where('student_id', $request->student_id)->get();
            $publicAttendances = $this->extractPublicCourse($attendances);
            return new CourseIndexResource($publicAttendances);
        }

        $attendances = Attendance::whereHas('course', function ($q) use ($request) {
            $q->where('title', 'like', "%$request->text%");
        })
            ->with(['course.instructor'])
            ->where('student_id', '=', $request->student_id)
            ->get();
        $publicAttendances = $this->extractPublicCourse($attendances);
        return new CourseIndexResource($publicAttendances);
    }

    private function extractPublicCourse($attendances)
    {
        return $attendances->filter(function ($attendance) {
            return $attendance->course->status === Course::STATUS_PUBLIC;
        });
    }
    /**
     * 講座詳細取得API
     *
     * @param CourseShowRequest $request
     * @return CourseShowResource
     */
    public function show(CourseShowRequest $request)
    {
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'course.instructor',
            'lessonAttendances'
        ])
        ->findOrFail($request->attendance_id);

        return new CourseShowResource($attendance);
    }
    /**
     * チャプター進捗状況、続きのレッスンID取得API
     *
     * @param Request $request
     * @return Resource
     */
    public function progress(Request $request)
    {
        return response()->json([]);
    }
}
