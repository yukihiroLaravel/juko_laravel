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
            ->firstOrFail();

        // 公開されているチャプターのみ抽出
        $publicChapters = Chapter::extractPublicChapter($attendance->course->chapters);
        $attendance->course->chapters = $publicChapters;

        // リクエストのチャプターIDと一致するチャプターのみ抽出
        $chapter = $attendance->course->chapters->filter(function($chapter) use ($request) {
                return $chapter->id === (int)$request->chapter_id;
            })
            ->first();

        return new AttendanceShowChapterResource([
            'attendance' => $attendance,
            'chapter' => $chapter
        ]);
    }
}
