<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\AttendanceShowRequest;
use App\Http\Resources\Student\AttendanceShowResource;
use App\Http\Requests\Student\AttendanceShowChapterRequest;
use App\Http\Resources\Student\AttendanceShowChapterResource;
use App\Model\Attendance;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Model\Chapter;

class AttendanceController extends Controller
{
    /**
     * 講座詳細取得API
     *
     * @param AttendanceShowRequest $request
     * @return AttendanceShowResource
     */
    public function show(AttendanceShowRequest $request)
    {
        $attendance = Attendance::with([
            'course.chapters.lessons',
            'course.instructor',
            'lessonAttendances'
        ])
        ->findOrFail($request->attendance_id);

        if ($attendance->student_id !== $request->user()->id){
            return response()->json([
                "result" => false,
                "message" => "Access forbidden."
            ], 403);
        }

        $publicChapters = Chapter::extractPublicChapter($attendance->course->chapters);
        $attendance->course->chapters = $publicChapters;
        return new AttendanceShowResource($attendance);
    }
    
    /** チャプター詳細情報を取得
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
