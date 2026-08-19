<?php

namespace App\Services\Student;

use App\Model\Student;

class QueryService
{
    /**
     * 選択された生徒の情報を取得
     */
    public function __invoke(int $studentId): Student
    {
        return Student::find($studentId);
    }
}
