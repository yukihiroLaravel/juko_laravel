<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
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
        $attendance = Attendance::factory()->create();
        $this->actingAs($attendance->student);

        // Act
        $response = $this->getJson(route('student.attendances.stuck-points', [
            'attendance_id' => $attendance->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    public function test_受講済みのレッスンがない場合_最初の公開レッスンを返す(): void
    {
        // Arrange
        $attendance = Attendance::factory()->create();
        $chapter = Chapter::factory()->create([
            'course_id' => $attendance->course->id,
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
        $this->actingAs($attendance->student);

        // 受講生2人を期限内で登録
        $student1 = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $attendance->course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $student2 = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $attendance->course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);

        // Act
        $response = $this->getJson(route('student.attendances.stuck-points', [
            'attendance_id' => $attendance->id,
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
        $attendance = Attendance::factory()->create();
        $chapter = Chapter::factory()->create([
            'course_id' => $attendance->course->id,
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
        $this->actingAs($attendance->student);

        // 受講生2人がともに targetLesson まで完了
        $student1 = Student::factory()->create();
        $attendance1 = Attendance::factory()->create([
            'course_id' => $attendance->course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $student2 = Student::factory()->create();
        $attendance2 = Attendance::factory()->create([
            'course_id' => $attendance->course->id,
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
        $response = $this->getJson(route('student.attendances.stuck-points', [
            'attendance_id' => $attendance->id,
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

    public function test_権限のない生徒_失敗(): void
    {
        // Arrange
        $owner = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $owner->id,
        ]);
        $otherStudent = Student::factory()->create();
        $this->actingAs($otherStudent);

        // Act
        $response = $this->getJson(route('student.attendances.stuck-points', [
            'attendance_id' => $attendance->id,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_バリデーションエラー_受講idが文字列(): void
    {
        // Arrange
        $attendance = Attendance::factory()->create();
        $this->actingAs($attendance->student);

        // Act
        $response = $this->getJson(route('student.attendances.stuck-points', [
            'attendance_id' => 'aaa',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attendance_id']);
    }

    public function test_バリデーションエラー_存在しない受講id(): void
    {
        // Arrange
        $attendance = Attendance::factory()->create();
        $this->actingAs($attendance->student);

        // Act
        $response = $this->getJson(route('student.attendances.stuck-points', [
            'attendance_id' => 99999,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attendance_id']);
    }

    public function test_バリデーションエラー_論理削除された受講id(): void
    {
        // Arrange
        $attendance = Attendance::factory()->create();
        $attendance->delete();
        $this->actingAs($attendance->student);

        // Act
        $response = $this->getJson(route('student.attendances.stuck-points', [
            'attendance_id' => $attendance->id,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attendance_id']);
    }
}
