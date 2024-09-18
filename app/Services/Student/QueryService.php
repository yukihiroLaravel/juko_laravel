<?php

namespace App\Services\Student;

use App\Model\Student;
use Illuminate\Database\Eloquent\Collection;

class QueryService
{
    /**
     * 選択された生徒の情報を取得
     *
     * @param int $studentId
     * @return Student
     */
    public function get(int $studentId): Student
    {
        return Student::
    }

    /**
     * 選択された生徒情報一覧を取得
     *
     * @param int $studentId
     * @return Collection<Student>
     */
    public function get(int $studentId): Collection
    {
        return Student::where('student_id', $studentId)->get();
    }
}
