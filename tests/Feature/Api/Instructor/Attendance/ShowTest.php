<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_マネージャーで受講状況を取得(): void
    {
        // Arrange — マネージャーが自分の講座の受講を取得
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'attendance_id',
                'attendance_deadline',
                'days_until_deadline',
                'course' => [
                    'course_id',
                    'title',
                    'image',
                    'status',
                    'capacity',
                    'deadline_type',
                    'course_deadline',
                ],
                'students_count',
            ],
        ]);
    }

    public function test_マネージャーで配下の講師で受講状況を取得(): void
    {
        // Arrange — マネージャーが配下講師の講座の受講を取得
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'attendance_id',
                'attendance_deadline',
                'days_until_deadline',
                'course' => [
                    'course_id',
                    'title',
                    'image',
                    'status',
                    'capacity',
                    'deadline_type',
                    'course_deadline',
                ],
                'students_count',
            ],
        ]);
    }

    public function test_講師で受講状況を取得(): void
    {
        // Arrange — 講師が自分の講座の受講を取得
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'attendance_id',
                'attendance_deadline',
                'days_until_deadline',
                'course' => [
                    'course_id',
                    'title',
                    'image',
                    'status',
                    'capacity',
                    'deadline_type',
                    'course_deadline',
                ],
                'students_count',
            ],
        ]);
    }

    public function test_レスポンスの値が正しい(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 30,
        ]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'attendance_id' => $attendance->id,
                'course' => [
                    'course_id' => $course->id,
                    'title' => $course->title,
                    'status' => $course->status,
                    'capacity' => 30,
                ],
                'students_count' => 1,
            ],
        ]);
    }

    public function test_定員未設定の場合capacityがnullで返る(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => null,
        ]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'course' => [
                    'capacity' => null,
                ],
            ],
        ]);
    }

    public function test_受講生が複数いる場合のカウント(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $students = Student::factory()->count(3)->create();
        foreach ($students as $student) {
            Attendance::factory()->create([
                'student_id' => $student->id,
                'course_id' => $course->id,
            ]);
        }
        $firstAttendance = Attendance::where('course_id', $course->id)->first();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $firstAttendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'students_count' => 3,
            ],
        ]);
    }

    public function test_受講期限切れの場合は閲覧不可(): void
    {
        // Arrange — 受講期限が過去の受講
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::yesterday(),
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_権限がない講師_失敗(): void
    {
        // Arrange — 他の講師の講座の受講を取得しようとする
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_マネージャーで配下でない講師の受講は取得不可(): void
    {
        // Arrange — マネージャーだが配下でない講師の講座
        $manager = Instructor::factory()->create();
        $unrelatedInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $unrelatedInstructor->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_論理削除済みの受講はバリデーションエラー(): void
    {
        // Arrange — 論理削除された受講
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $attendanceId = $attendance->id;
        $attendance->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendanceId]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attendance_id']);
    }

    public function test_存在しないattendance_idはバリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => 99999]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attendance_id']);
    }

    public function test_バリデーションエラー_文字列(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'attendance_id',
        ]);
    }

    public function test_講座に期限設定がある場合のレスポンス(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'deadline_type' => 'fixed',
        ]);
        CourseDeadline::factory()->create([
            'course_id' => $course->id,
            'fixed_date' => '2026-12-31',
        ]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'course' => [
                    'deadline_type' => 'fixed',
                ],
            ],
        ]);
        $response->assertJsonPath('data.course.course_deadline.fixed_date', '2026-12-31');
    }
}
