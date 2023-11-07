<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Model\Attendance;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Student\AttendanceIndexRequest;
use App\Http\Resources\Student\AttendanceIndexResource;
use App\Model\Course;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Requests\Student\AttendanceShowChapterRequest;
use App\Http\Resources\Student\AttendanceShowChapterResource;
use App\Model\Chapter;

class AttendanceController extends Controller
{
    /**
     * 受講中講座一覧取得API
     *
     * @param AttendanceIndexRequest $request
     * @return AttendanceIndexResource
     */
    public function index (AttendanceIndexRequest $request) {
        $studentId = Auth::id();

        if (!$request->search_word) {
            $attendances = Attendance::with('course.instructor')
            ->where('student_id', $studentId)
            ->whereHas('course', function (Builder $query) {
                $query->where('status', Course::STATUS_PUBLIC);
            })->get();
            return new AttendanceIndexResource($attendances);
        }

        $attendances = Attendance::with('course.instructor')
        ->where('student_id', $studentId)
        ->whereHas('course', function (Builder $query) use($request) {
            $query->where('title', 'like', "%{$request->search_word}%");
            $query->where('status', Course::STATUS_PUBLIC);
        })->get();

        return new AttendanceIndexResource($attendances);
    }

    /**
     * チャプター詳細情報を取得
     *
     * @param AttendanceShowChapterRequest $request
     * @return AttendanceShowChapterResource
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
