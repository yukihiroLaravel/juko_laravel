<?php

namespace Tests\Feature\Api\Student\LessonAttendance;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
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
            route('student.lesson-attendance.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
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
            route('student.lesson-attendance.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
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
            route('student.lesson-attendance.patch-status', ['lesson_attendance_id' => 'abc']),
            ['status' => 'aaa']
        );

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'lesson_attendance_id',
            'status',
        ]);
    }
}
