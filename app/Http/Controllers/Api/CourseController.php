<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CourseShowRequest;
use App\Http\Requests\CourseIndexRequest;
use App\Http\Resources\CourseIndexResource;
use App\Http\Resources\CourseShowResource;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Chapter;

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
            $attendances = Attendance::with(['course.instructor'])->where('student_id', $request->user()->id)->get();
            $publicAttendances = $this->extractPublicCourse($attendances);
            return new CourseIndexResource($publicAttendances);
        }

        // 検索ワードで講座を検索
        $attendances = Attendance::with(['course.instructor'])
            ->where('student_id', $request->user()->id)
            ->whereHas('course', function ($query) use ($request) {
                $query->where('title', 'like', "%{$request->text}%");
            })
            ->get();

        $publicAttendances = $this->extractPublicCourse($attendances);
        return new CourseIndexResource($publicAttendances);
    }

    /**
     * 公開中の講座を抽出
     *
     * @param \Illuminate\Support\Collection $attendances
     * @return \Illuminate\Support\Collection
     */
    private function extractPublicCourse($attendances)
    {
        return $attendances->filter(function ($attendance) {
            return $attendance->course->status === Course::STATUS_PUBLIC;
        })
        ->values();
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

        $publicChapters = Chapter::extractPublicChapter($attendance->course->chapters);
        $attendance->course->chapters = $publicChapters;
        return new CourseShowResource($attendance);
    }
}
