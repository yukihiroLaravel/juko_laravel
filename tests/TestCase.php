<?php

namespace Tests;

use App\Model\Instructor;
use App\Model\Student;
use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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

    /**
     * 指定した文で始まるクエリが実行された直後に例外を投げる
     *
     * 複数の更新の途中で失敗したときに、先に実行した更新が残らないことを確かめるために使う。
     */
    protected function failAfterQuery(string $sqlPrefix): void
    {
        DB::listen(function (QueryExecuted $query) use ($sqlPrefix): void {
            if (str_starts_with($query->sql, $sqlPrefix)) {
                throw new RuntimeException('テストのために失敗させた');
            }
        });
    }
}
