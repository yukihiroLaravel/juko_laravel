<?php

namespace App\Dto\Student\Attendance;

class ContinueFromDto
{
    public function __construct(
        private readonly int $chapterId,
        private readonly string $chapterTitle,
        private readonly int $lessonId,
        private readonly string $lessonTitle
    ) {}

    public function getChapterId(): int
    {
        return $this->chapterId;
    }

    public function getChapterTitle(): string
    {
        return $this->chapterTitle;
    }

    public function getLessonId(): int
    {
        return $this->lessonId;
    }

    public function getLessonTitle(): string
    {
        return $this->lessonTitle;
    }

    public function toArray(): array
    {
        return [
            'chapter_id' => $this->chapterId,
            'chapter_title' => $this->chapterTitle,
            'lesson_id' => $this->lessonId,
            'lesson_title' => $this->lessonTitle,
        ];
    }
}
