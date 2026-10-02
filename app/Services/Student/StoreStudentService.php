<?php

namespace App\Services\Student;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Student;
use Illuminate\Validation\ValidationException;

class StoreStudentService
{
    /**
     * 受講生を登録し、講座への受講を作成する
     *
     * 定員の判定と受講の作成を一貫させるため、呼び出し側でトランザクションを張ること。
     * 行ロックはトランザクションの内側でのみ効く。
     *
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): void
    {
        $course = Course::lockForUpdate()->findOrFail($data['course_id']);

        // 定員チェック
        if (! $course->hasCapacity()) {
            throw ValidationException::withMessages([
                'course_id' => [
                    'This course has already reached its capacity.',
                ],
            ]);
        }

        $student = Student::create([
            'given_name_by_instructor' => $data['given_name_by_instructor'],
            'email' => $data['email'],
        ]);

        Attendance::create([
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);
    }
}
