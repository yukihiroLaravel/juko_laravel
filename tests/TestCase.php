<?php

namespace Tests;

use App\Model\Instructor;
use App\Model\Student;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * 生徒でログインする
     */
    protected function loginAsStudent(int $studentId = 1): void
    {
        $student = Student::find($studentId);
        $this->actingAs($student, 'web');
    }

    /**
     * マネージャーでログインする
     */
    protected function loginAsManager(int $managerId = 1): void
    {
        $manager = Instructor::find($managerId);
        $this->actingAs($manager, 'instructor');
    }

    /**
     * 講師でログインする
     */
    protected function loginAsInstructor(int $instructorId = 2): void
    {
        $instructor = Instructor::find($instructorId);
        $this->actingAs($instructor, 'instructor');
    }
}
