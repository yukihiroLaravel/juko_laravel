<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteAllLessonsTest extends TestCase
{
    use RefreshDatabase;

    public function test_指定したチャプターのレッスンを全て完了_成功(): void
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
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => $attendance->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => 'completed_attendance',
        ]);
    }

    public function test_権限がない生徒_失敗(): void
    {
        // Arrange — 別の生徒のAttendanceにアクセス
        $owner = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $owner->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $unauthorizedStudent = Student::factory()->create();
        $this->actingAs($unauthorizedStudent);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => $attendance->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_権限がないチャプター_失敗(): void
    {
        // Arrange — 別の講座のチャプターを指定
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $otherCourse = Course::factory()->create();
        $otherChapter = Chapter::factory()->create(['course_id' => $otherCourse->id]);
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => $attendance->id,
            'chapter_id' => $otherChapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, invalid chapter.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => 'aaa',
            'chapter_id' => 'bbb',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'attendance_id',
            'chapter_id',
        ]);
    }

    public function test_まとめて完了にしたレッスンにも完了した日時が記録される(): void
    {
        // Arrange — まだ終えていないレッスンと、過去に終えたレッスン
        $firstCompletedAt = CarbonImmutable::parse('2026-01-01 10:00:00');
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $unfinishedLesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $finishedLesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 2]);
        $unfinished = LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $unfinishedLesson->id,
        ]);
        $finished = LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $finishedLesson->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => $firstCompletedAt,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => $attendance->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert — 未完了のものは日時が記録され、既に終えていたものは最初の日時が保たれる
        $response->assertStatus(200);
        $this->assertNotNull($unfinished->fresh()->completed_at);
        $this->assertTrue($firstCompletedAt->equalTo($finished->fresh()->completed_at));
    }
}
