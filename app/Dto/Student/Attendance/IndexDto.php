<?php

namespace App\Dto\Student\Attendance;

class IndexDto
{
    /**
     * @param  string|null  $searchWord
     */
    public function __construct(private readonly int $studentId, private $searchWord) {}

    public function getStudentId()
    {
        return $this->studentId;
    }

    public function getSearchWord()
    {
        return $this->searchWord;
    }
}
