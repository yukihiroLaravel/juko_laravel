<?php

namespace App\Dto\Instructor\Attendance;

readonly class FollowUpStudentDto
{
    public function __construct(
        public int $studentId,
        public string $lastName,
        public string $firstName,
        public string $email,
        public ?string $latestLoginAt,
        public ?int $incompleteChapterId,
        public ?string $incompleteChapterTitle,
    ) {}
}
