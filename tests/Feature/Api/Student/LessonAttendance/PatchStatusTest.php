<?php

namespace Tests\Feature\Api\Student\LessonAttendance;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatchStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_レッスン受講状態を更新_成功(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lessonAttendance = LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => 'before_attendance']
        );

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'id' => $lessonAttendance->id,
            'status' => 'before_attendance',
        ]);
    }

    public function test_レッスン受講状態を更新_他の生徒の受講状態を更新_失敗(): void
    {
        // Arrange — 別の生徒のLessonAttendanceにアクセス
        $owner = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $owner->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lessonAttendance = LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
        ]);
        $otherStudent = Student::factory()->create();
        $this->actingAs($otherStudent);

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => 'before_attendance']
        );

        // Assert
        $response->assertStatus(403);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => 'abc']),
            ['status' => 'aaa']
        );

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'lesson_attendance_id',
            'status',
        ]);
    }

    public function test_レッスンを終えると完了した日時が記録される(): void
    {
        // Arrange
        $lessonAttendance = $this->createLessonAttendanceForActingStudent();

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE]
        );

        // Assert
        $response->assertStatus(200);
        $this->assertNotNull($lessonAttendance->fresh()->completed_at);
    }

    public function test_レッスンを終えるまでは完了した日時が記録されない(): void
    {
        // Arrange
        $lessonAttendance = $this->createLessonAttendanceForActingStudent();

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => LessonAttendance::STATUS_IN_ATTENDANCE]
        );

        // Assert
        $response->assertStatus(200);
        $this->assertNull($lessonAttendance->fresh()->completed_at);
    }

    public function test_一度終えたレッスンを学び直しても最初に終えた日時が保たれる(): void
    {
        // Arrange — 過去に終えたレッスンを受講中に戻した状態
        $firstCompletedAt = CarbonImmutable::parse('2026-01-01 10:00:00');
        $lessonAttendance = $this->createLessonAttendanceForActingStudent([
            'status' => LessonAttendance::STATUS_IN_ATTENDANCE,
            'completed_at' => $firstCompletedAt,
        ]);

        // Act — 学び直して再び完了にする
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE]
        );

        // Assert — 最初に終えた日時は上書きされない
        $response->assertStatus(200);
        $this->assertTrue($firstCompletedAt->equalTo($lessonAttendance->fresh()->completed_at));
    }

    /**
     * ログイン中の受講生に紐づくレッスン受講状況を用意する
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createLessonAttendanceForActingStudent(array $attributes = []): LessonAttendance
    {
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($student);

        return LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            ...$attributes,
        ]);
    }
}
