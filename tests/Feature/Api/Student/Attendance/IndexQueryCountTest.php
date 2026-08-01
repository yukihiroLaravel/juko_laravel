<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IndexQueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講一覧の問い合わせ回数は受講件数が増えても変わらない(): void
    {
        // Arrange
        $oneCourseStudent = $this->createStudentWithAttendances(1);
        $manyCoursesStudent = $this->createStudentWithAttendances(5);

        // Act
        $oneCourseQueryCount = $this->countQueriesOnIndex($oneCourseStudent);
        $manyCoursesQueryCount = $this->countQueriesOnIndex($manyCoursesStudent);

        // Assert
        $this->assertSame(
            $oneCourseQueryCount,
            $manyCoursesQueryCount,
            '受講件数に比例して問い合わせが増えている（N+1が発生している）'
        );
    }

    /**
     * 指定件数の受講を持つ受講生を用意する
     */
    private function createStudentWithAttendances(int $attendanceCount): Student
    {
        $student = Student::factory()->create();

        foreach (range(1, $attendanceCount) as $ignored) {
            $course = Course::factory()->create();
            $chapter = Chapter::factory()->create(['course_id' => $course->id]);
            Lesson::factory()->count(2)->create(['chapter_id' => $chapter->id]);
            Attendance::factory()->create([
                'student_id' => $student->id,
                'course_id' => $course->id,
            ]);
        }

        return $student;
    }

    /**
     * 受講一覧取得で発行された問い合わせ回数を数える
     */
    private function countQueriesOnIndex(Student $student): int
    {
        $this->actingAs($student);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson(route('student.attendances.index'))->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queryCount;
    }
}
