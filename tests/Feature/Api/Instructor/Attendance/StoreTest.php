<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講登録_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.attendance.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
    }

    public function test_受講登録_成功_レッスン受講レコードが一括作成される(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(3)->create(['chapter_id' => $chapter->id]);
        $student = Student::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.attendance.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $attendance = Attendance::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();
        $this->assertNotNull($attendance);
        $this->assertCount(3, LessonAttendance::where('attendance_id', $attendance->id)->get());
        foreach ($lessons as $lesson) {
            $this->assertDatabaseHas('lesson_attendances', [
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
            ]);
        }
    }

    public function test_定員オーバー_失敗(): void
    {
        // Arrange — 定員1の講座に既に1名登録済み
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 1,
        ]);
        Attendance::factory()->create(['course_id' => $course->id]);
        $student = Student::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.attendance.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id' => 'This course has already reached its capacity.',
        ]);
    }

    public function test_重複登録_失敗(): void
    {
        // Arrange — 同じ生徒が同じ講座に既に登録済み
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.attendance.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'student_id' => 'Attendance record already exists.',
        ]);
    }

    public function test_重複登録は定員チェックより優先される(): void
    {
        // Arrange — 定員1の講座に同じ生徒が既に登録済み
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 1,
        ]);
        $student = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.attendance.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert — 定員オーバーではなく重複エラーが返る
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'student_id' => 'Attendance record already exists.',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // Arrange — 他の講師の講座に受講登録しようとする
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $student = Student::factory()->create();
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.attendance.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.attendance.store'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'student_id',
        ]);
    }
}
