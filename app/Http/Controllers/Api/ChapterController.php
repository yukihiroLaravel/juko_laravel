<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChapterGetRequest;
use App\Http\Resources\ChapterGetResponse;
use App\Http\Resources\ChapterStoreResponse;
use App\Model\Attendance;
use App\Model\Chapter;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChapterController extends Controller
{
    /**
     * チャプター詳細情報を取得
     *
     * @param ChapterGetRequest $request
     * @return ChapterGetResponse
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

        return new ChapterGetResponse($attendance, $request->chapter_id);
    }
    public function index($id)
    {
        $chapter = Chapter::findOrFail($id);
        return new ChapterStoreResponse();
    }
}
