<?php

namespace App\Services\Instructor;

use App\Model\Instructor;
use Illuminate\Database\Eloquent\Collection;

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

     /**
     * 管理している講師の情報を取得
     *
     * @param int $instructorId
     * @return Collection<Student>
     */
    public function getStudentsByInstructorId(int $instructorId): Collection
    {
        return Student::where('instructor_id', $instructorId)->get();
    }
}
