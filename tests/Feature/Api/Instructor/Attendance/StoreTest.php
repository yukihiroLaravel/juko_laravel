<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
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
        $response = $this->postJson(route('instructor.attendances.store'), [
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
        $this->assertCount(3, LessonAttendance::where('attendance_id', $attendance->id)->get());
        foreach ($lessons as $lesson) {
            $this->assertDatabaseHas('lesson_attendances', [
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
            ]);
        }
    }

    public function test_受講登録_下書きレッスンには受講状況が作成されない(): void
    {
        // Arrange — 公開中・限定公開・下書きのレッスンを混在させる
        $instructor = Instructor::factory()->create();
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
        $response = $this->postJson(route('instructor.attendance.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert — 下書きではないレッスンの分のみ受講状況が作成される
        $response->assertStatus(200);
        $attendance = Attendance::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();
        $this->assertNotNull($attendance);
        $this->assertCount(2, LessonAttendance::where('attendance_id', $attendance->id)->get());
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $publicLesson->id,
        ]);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $privateLesson->id,
        ]);
        $this->assertDatabaseMissing('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $draftLesson->id,
        ]);
    }

    public function test_受講登録_全レッスンが下書きの場合は受講状況が作成されない(): void
    {
        // Arrange — 講座内の全レッスンが下書き
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
        $response = $this->postJson(route('instructor.attendance.store'), [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Assert — 受講登録は成立するが、受講状況は1件も作成されない
        $response->assertStatus(200);
        $attendance = Attendance::where('course_id', $course->id)
            ->where('student_id', $student->id)
            ->first();
        $this->assertNotNull($attendance);
        $this->assertCount(0, LessonAttendance::where('attendance_id', $attendance->id)->get());
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

    public function test_権限がない講師_失敗(): void
    {
        // Arrange — 他の講師の講座に受講登録しようとする
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $student = Student::factory()->create();
        $otherInstructor = Instructor::factory()->create();
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

    public function test_バリデーションエラー(): void
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
