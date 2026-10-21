<?php

namespace Tests;

use App\Model\Instructor;
use App\Model\Student;
use Closure;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

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

    /**
     * 渡した処理の中で発行されたクエリの数を返す
     */
    protected function countQueries(Closure $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queryCount;
    }
}
