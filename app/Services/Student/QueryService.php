<?php

namespace App\Services\Student;

use App\Model\Student;
use Http\Controllers\Api\Instructor;
use Http\Controllers\Api\Manager;

class QueryService
{
    /**
     * 選択された生徒の情報を取得
     *
     * @param int $studentId
     * @return Student
     */
    public function getStudent(int $studentId): Student
    {
        return Student::find($studentId);
    }
}
