<?php

namespace App\Dto\Student\Attendance;

class IndexDto
{
    /** @var int */
    private $studentId;

    /** @var string|null */
    private $searchWord;

    public function __construct(int $studentId, $searchWord)
    {
        $this->studentId = $studentId;
        $this->searchWord = $searchWord;
    }

    public function getStudentId()
    {
        return $this->studentId;
    }

    public function getSearchWord()
    {
        return $this->searchWord;
    }
}
