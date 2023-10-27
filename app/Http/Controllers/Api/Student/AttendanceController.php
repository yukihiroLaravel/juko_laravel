<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\AttendanceShowChapterRequest;
use App\Http\Resources\Student\AttendanceShowChapterResource;
use App\Model\Attendance;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Model\Chapter;

class AttendanceController extends Controller
{
    /**
     * チャプター詳細情報を取得
     *
     * @param AttendanceShowChapterRequest $request
     * @return AttendanceShowChapterResource
     * @throws HttpException
     */
    public function showChapter(AttendanceShowChapterRequest $request)
    {
        $attendance = Attendance::with([
                'course.chapters.lessons',
                'lessonAttendances'
            ])
            ->where('id', $request->attendance_id)
            ->first();

        if ($attendance === null) {
            throw new HttpException(404, "Not found attendance.");
        }

        $publicChapters = Chapter::extractPublicChapter($attendance->course->chapters);
        $attendance->course->chapters = $publicChapters;
        return new AttendanceShowChapterResource($attendance, $request->chapter_id);
    }
}
