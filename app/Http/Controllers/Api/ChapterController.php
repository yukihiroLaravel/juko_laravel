<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChapterGetRequest;
use App\Http\Resources\ChapterShowResource;
use App\Model\Attendance;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Model\Chapter;

class ChapterController extends Controller
{
    /**
     * チャプター詳細情報を取得
     *
     * @param ChapterGetRequest $request
     * @return ChapterShowResource
     * @throws HttpException
     */
    public function show(ChapterGetRequest $request)
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

        $publicChapters = $this->extractPublicChapter($attendance->course->chapters);
        $attendance->course->chapters = $publicChapters;
        return new ChapterShowResource($attendance, $request->chapter_id);
    }

    /**
     * 公開中のチャプターを抽出
     *
     * @param \Illuminate\Support\Collection $chapters
     * @return \Illuminate\Support\Collection
     */
    private function extractPublicChapter($chapters)
    {
        return $chapters->filter(function ($chapter) {
            return $chapter->status === Chapter::STATUS_PUBLIC;
        });
    }
}
