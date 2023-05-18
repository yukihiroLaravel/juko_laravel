<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChapterShowResource extends JsonResource
{
    private int $chapterId;
    public function __construct($attendances, $chapterId)
    {
        $this->chapterId = $chapterId;
        parent::__construct($attendances);
    }

    public function toArray($request)
    {
        $chapterId = $this->chapterId;
        $chapter = $this->resource->course->chapters->filter(function($chapter) use ($chapterId) {
                return $chapter->id === (int)$chapterId;
            })
            ->first();

        if ($chapter === null) throw new HttpException(404, "Not found chapter.");

        $lessons = $chapter->lessons->map(function($lesson) {
            $lessonId = $lesson->id;
            $lessonAttendance = $this->resource->lessonAttendances->filter(function ($lessonAttendance) use($lessonId) {
                return $lessonAttendance->lesson_id === $lessonId;
            })
            ->map(function($lessonAttendance) {
                return [
                    'lesson_attendance_id' => $lessonAttendance->id,
                    'status' => $lessonAttendance->status,
                ];
            })
            ->first();
            return [
                'lesson_id' => $lesson->id,
                'title' => $lesson->title,
                'url' => $lesson->url,
                'remarks' => $lesson->remarks,
                'lessonAttendance' => $lessonAttendance
            ];
        });

        return [
            'chapter_id' => $chapter->id,
            'title' => $chapter->title,
            'lessons' => $lessons,
        ];
    }
}

