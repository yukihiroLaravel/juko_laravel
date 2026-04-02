<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpiringTest extends TestCase
{
    use RefreshDatabase;

    public function test_thresholdsごとに排他的にグループ化される(): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01'));

        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // 2日後に期限切れ（3日以内グループ）
        $student3days = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student3days->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-03'),
        ]);

        // 5日後に期限切れ（4〜7日グループ）
        $student7days = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student7days->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-06'),
        ]);

        // 20日後に期限切れ（8〜30日グループ）
        $student30days = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student30days->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-21'),
        ]);

        // Act
        $response = $this->getJson(route('instructor.course.attendance.expiring', [
            'course_id' => $course->id,
            'thresholds' => [3, 7, 30],
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);

        // 3日以内グループ
        $this->assertEquals(3, $data[0]['days']);
        $this->assertCount(1, $data[0]['students']);
        $this->assertEquals($student3days->id, $data[0]['students'][0]['student_id']);

        // 4〜7日グループ
        $this->assertEquals(7, $data[1]['days']);
        $this->assertCount(1, $data[1]['students']);
        $this->assertEquals($student7days->id, $data[1]['students'][0]['student_id']);

        // 8〜30日グループ
        $this->assertEquals(30, $data[2]['days']);
        $this->assertCount(1, $data[2]['students']);
        $this->assertEquals($student30days->id, $data[2]['students'][0]['student_id']);

        CarbonImmutable::setTestNow();
    }

    public function test_期限なしの受講生は含まれない(): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01'));

        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // 期限なしの受講生
        $studentNoDeadline = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $studentNoDeadline->id,
            'course_id' => $course->id,
            'attendance_deadline' => null,
        ]);

        // Act
        $response = $this->getJson(route('instructor.course.attendance.expiring', [
            'course_id' => $course->id,
            'thresholds' => [30],
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertCount(0, $data[0]['students']);

        CarbonImmutable::setTestNow();
    }

    public function test_期限切れ済みの受講生は含まれない(): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01'));

        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // 昨日に期限切れ
        $studentExpired = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $studentExpired->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-03-31'),
        ]);

        // Act
        $response = $this->getJson(route('instructor.course.attendance.expiring', [
            'course_id' => $course->id,
            'thresholds' => [30],
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertCount(0, $data[0]['students']);

        CarbonImmutable::setTestNow();
    }

    public function test_境界値_ちょうどthreshold日後の受講生は含まれる(): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01'));

        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // ちょうど3日後
        $student = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-04'),
        ]);

        // Act
        $response = $this->getJson(route('instructor.course.attendance.expiring', [
            'course_id' => $course->id,
            'thresholds' => [3],
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data[0]['students']);
        $this->assertEquals($student->id, $data[0]['students'][0]['student_id']);

        CarbonImmutable::setTestNow();
    }

    public function test_境界値_当日の受講生は含まれない(): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01'));

        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // 当日が期限
        $student = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-01'),
        ]);

        // Act
        $response = $this->getJson(route('instructor.course.attendance.expiring', [
            'course_id' => $course->id,
            'thresholds' => [3],
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(0, $data[0]['students']);

        CarbonImmutable::setTestNow();
    }

    public function test_レスポンスにdays_until_expiryが含まれる(): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01'));

        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        $student = Student::factory()->create([
            'last_name' => '山田',
            'first_name' => '太郎',
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-11'),
        ]);

        // Act
        $response = $this->getJson(route('instructor.course.attendance.expiring', [
            'course_id' => $course->id,
            'thresholds' => [30],
        ]));

        // Assert
        $response->assertStatus(200);
        $studentData = $response->json('data.0.students.0');
        $this->assertEquals($student->id, $studentData['student_id']);
        $this->assertEquals('山田 太郎', $studentData['name']);
        $this->assertEquals('2026-04-11', $studentData['expires_at']);
        $this->assertEquals(10, $studentData['days_until_expiry']);

        CarbonImmutable::setTestNow();
    }

    public function test_受講期限が近い順にソートされる(): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01'));

        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // 20日後
        $studentLater = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $studentLater->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-21'),
        ]);

        // 5日後
        $studentSooner = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $studentSooner->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-06'),
        ]);

        // Act
        $response = $this->getJson(route('instructor.course.attendance.expiring', [
            'course_id' => $course->id,
            'thresholds' => [30],
        ]));

        // Assert
        $response->assertStatus(200);
        $students = $response->json('data.0.students');
        $this->assertCount(2, $students);
        $this->assertEquals($studentSooner->id, $students[0]['student_id']);
        $this->assertEquals($studentLater->id, $students[1]['student_id']);

        CarbonImmutable::setTestNow();
    }

    public function test_他講座の受講生は含まれない(): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01'));

        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $otherCourse = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // 対象講座の受講生
        $studentInCourse = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $studentInCourse->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-05'),
        ]);

        // 別講座の受講生
        $studentInOther = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $studentInOther->id,
            'course_id' => $otherCourse->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-05'),
        ]);

        // Act
        $response = $this->getJson(route('instructor.course.attendance.expiring', [
            'course_id' => $course->id,
            'thresholds' => [30],
        ]));

        // Assert
        $response->assertStatus(200);
        $students = $response->json('data.0.students');
        $this->assertCount(1, $students);
        $this->assertEquals($studentInCourse->id, $students[0]['student_id']);

        CarbonImmutable::setTestNow();
    }

    public function test_権限のない講師_失敗(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.expiring', [
            'course_id' => $course->id,
            'thresholds' => [30],
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_バリデーションエラー_thresholdsが未指定(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.expiring', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['thresholds']);
    }

    public function test_バリデーションエラー_thresholdsが0以下(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.expiring', [
            'course_id' => $course->id,
            'thresholds' => [0],
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['thresholds.0']);
    }

    public function test_バリデーションエラー_存在しない講座id(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.attendance.expiring', [
            'course_id' => 99999,
            'thresholds' => [30],
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }
}
