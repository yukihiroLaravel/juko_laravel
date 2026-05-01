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

class ShowStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_当日の出席状況を取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_今月の出席状況を取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_無効のパラメータ(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.show-status', [
            'course_id' => 99999,
            'period' => 'invalid',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'period',
        ]);
    }

    public function test_権限のない講師_失敗(): void
    {
        // Arrange — 他の講師の講座を指定
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_受講生がいない場合_平均進捗率は0(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['average_progress_rate' => 0]);
    }

    public function test_レッスンが0件の場合_平均進捗率は0(): void
    {
        // Arrange — 講座にチャプター/レッスンが無く、受講生のみ存在
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['average_progress_rate' => 0]);
    }

    public function test_平均進捗率の計算が正しい(): void
    {
        // Arrange — 受講生2名 × レッスン4本、当日完了が5件 → floor(5 / (2 * 4) * 100) = 62
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(4)->create(['chapter_id' => $chapter->id]);

        $studentA = Student::factory()->create();
        $attendanceA = Attendance::factory()->create(['student_id' => $studentA->id, 'course_id' => $course->id]);
        $studentB = Student::factory()->create();
        $attendanceB = Attendance::factory()->create(['student_id' => $studentB->id, 'course_id' => $course->id]);

        // 受講生A: 4レッスンのうち3件を完了
        foreach ($lessons as $i => $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendanceA->id,
                'lesson_id' => $lesson->id,
                'status' => $i < 3
                    ? LessonAttendance::STATUS_COMPLETED_ATTENDANCE
                    : LessonAttendance::STATUS_BEFORE_ATTENDANCE,
            ]);
        }
        // 受講生B: 4レッスンのうち2件を完了
        foreach ($lessons as $i => $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendanceB->id,
                'lesson_id' => $lesson->id,
                'status' => $i < 2
                    ? LessonAttendance::STATUS_COMPLETED_ATTENDANCE
                    : LessonAttendance::STATUS_BEFORE_ATTENDANCE,
            ]);
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert — completed: 5 / (students: 2 × lessons: 4) × 100 = 62.5 → floor → 62
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 5,
            'average_progress_rate' => 62,
        ]);
    }
}
