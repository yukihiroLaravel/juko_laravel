<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
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

    /** AC-ENROLL-006 */
    public function test_受講登録で下書きでないレッスンの受講状況が未着手で作られる(): void
    {
        // Arrange — 公開・非公開・下書きのレッスンを混在させる
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $privateLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);
        $draftLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);
        $student = Student::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.attendances.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => true]);
        $attendance = Attendance::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();
        $this->assertNotNull($attendance);
        $this->assertCount(2, LessonAttendance::where('attendance_id', $attendance->id)->get());
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $publicLesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $privateLesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
        $this->assertDatabaseMissing('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $draftLesson->id,
        ]);
    }

    /** AC-ENROLL-007 */
    public function test_全レッスンが下書きの講座は受講だけが登録され受講状況は作られない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->count(3)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);
        $student = Student::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.attendances.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert
        $response->assertStatus(200);
        $attendance = Attendance::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();
        $this->assertNotNull($attendance);
        $this->assertCount(0, LessonAttendance::where('attendance_id', $attendance->id)->get());
    }

    /** AC-ENROLL-003 */
    public function test_定員に達した講座には受講を登録できない(): void
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
        $response = $this->postJson(route('instructor.attendances.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id' => 'This course has already reached its capacity.',
        ]);
    }

    /** AC-ENROLL-002 */
    public function test_同じ受講生を同じ講座に二重に登録できない(): void
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
        $response = $this->postJson(route('instructor.attendances.store'), [
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
        $response = $this->postJson(route('instructor.attendances.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert — 定員オーバーではなく重複エラーが返る
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'student_id' => 'Attendance record already exists.',
        ]);
    }

    /** AC-AUTHZ-001 */
    public function test_他の講師の講座には受講を登録できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $student = Student::factory()->create();
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.attendances.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_講座と受講生の指定がなければ受講を登録できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.attendances.store'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'student_id',
        ]);
    }
}
