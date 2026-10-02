<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StuckPointsTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01'));
    }

    #[\Override]
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_講座の期限内の受講が2人未満の場合_空のコレクションを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    public function test_受講済みのレッスンがない場合_最初の公開レッスンを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
            'title' => 'チャプター1',
        ]);
        $firstLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 1,
            'title' => 'レッスン1',
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);
        $this->actingAs($instructor, 'instructor');

        // 受講生2人を期限内で登録
        $student1 = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $student2 = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'chapter_id' => $chapter->id,
            'title' => 'チャプター1',
        ]);
        $response->assertJsonFragment([
            'lesson_id' => $firstLesson->id,
            'title' => 'レッスン1',
        ]);
    }

    public function test_受講済み最多数のレッスンが最終レッスン以外の場合_次のレッスンを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
            'title' => 'チャプター1',
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 1,
        ]);
        $targetLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);
        $nextLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 3,
            'title' => '次のレッスン',
        ]);
        $this->actingAs($instructor, 'instructor');

        // 受講生2人がともに targetLesson まで完了
        $student1 = Student::factory()->create();
        $attendance1 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $student2 = Student::factory()->create();
        $attendance2 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $targetLesson->id,
            'attendance_id' => $attendance1->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-05-02'),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $targetLesson->id,
            'attendance_id' => $attendance2->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-05-03'),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert — targetLesson の次の nextLesson が返る
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'chapter_id' => $chapter->id,
            'title' => 'チャプター1',
        ]);
        $response->assertJsonFragment([
            'lesson_id' => $nextLesson->id,
            'title' => '次のレッスン',
        ]);
    }

    public function test_権限のない講師_失敗(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_バリデーションエラー_講座idが文字列(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
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
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
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
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }
}
