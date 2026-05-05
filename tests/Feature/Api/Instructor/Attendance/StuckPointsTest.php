<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StuckPointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_権限のない講師_失敗(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_空配列を返す_成功(): void  // 受講期限がない場合を想定
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertExactJson([]);
    }

    public function test_受講生の止まっている箇所を返す_成功(): void    // 完了済みレッスンがない場合を想定（最初の公開レッスンを返す）
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01'));

        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        CourseDeadline::factory()->create([
            'course_id' => $course->id,
            'fixed_date' => CarbonImmutable::parse('2026-05-31'),
        ]);

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
        ]);

        $firstLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 1,
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);

        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);

        // Act
        $response = $this->getJson(route('instructor.course.attendance.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.chapter_id', $chapter->id);
        $response->assertJsonPath('data.0.chapter_title', $chapter->title);
        $response->assertJsonPath('data.0.lessons.0.lesson_id', $firstLesson->id);
        $response->assertJsonPath('data.0.lessons.0.lesson_title', $firstLesson->title);
    }

    public function test_バリデーションエラー_講座idが文字列(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.stuck-points', [
            'course_id' => 'aaa',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }

    public function test_バリデーションエラー_存在しない講座id(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.stuck-points', [
            'course_id' => 99999,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }

    public function test_バリデーションエラー_論理削除された講座id(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }
}
