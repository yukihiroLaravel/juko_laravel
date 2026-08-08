<?php

namespace App\Dto\Student\Attendance;

class IndexDto
{
    public function __construct(
        private readonly int $studentId,
        private readonly ?string $searchWord
    ) {}

    public function getStudentId(): int
    {
        return $this->studentId;
    }

    public function getSearchWord(): ?string
    {
        return $this->searchWord;
    }
}
