<?php

namespace App\Services\Student;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreStudentService
{
    public function __invoke(array $data): void
    {
        DB::transaction(function () use ($data) {

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
        });
    }
}
