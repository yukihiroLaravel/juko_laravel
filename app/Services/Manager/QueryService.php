<?php

namespace App\Services\Manager;

use App\Model\Student;

class QueryService
{
    /**
     * 選択された生徒の情報を取得
     *
     * @param int $StudentId
     * @return Student
     */
    public function getStudent(int $StudentId): Student
    {
        return Student::find($StudentId);
    }
}
