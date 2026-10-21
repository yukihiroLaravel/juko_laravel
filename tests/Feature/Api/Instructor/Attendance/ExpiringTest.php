<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ExpiringTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01'));
    }

    #[\Override]
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /**
     * いずれの区間にも含めない受講生の受講期限
     *
     * @return array<string, array{?string, list<int>}>
     */
    public static function excludedDeadlines(): array
    {
        return [
            '受講期限なし' => [null, [30]],
            '期限切れ済み（昨日が期限）' => ['2026-03-31', [30]],
            '当日が期限' => ['2026-04-01', [3]],
        ];
    }

    /**
     * 422 になる入力
     *
     * @return array<string, array{bool, array<string, list<int>>, string}>
     */
    public static function invalidInputs(): array
    {
        return [
            '日数を指定しない' => [true, [], 'thresholds'],
            '日数が 1 未満' => [true, ['thresholds' => [0]], 'thresholds.0'],
            '存在しない講座' => [false, ['thresholds' => [30]], 'course_id'],
        ];
    }

    /** AC-ANALYTICS-028・AC-ANALYTICS-030 */
    public function test_指定した日数ごとに受講期限が近い区間へ重複なく振り分ける(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // 2日後に期限切れ
        $student3days = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student3days->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-03'),
        ]);

        // 5日後に期限切れ
        $student7days = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student7days->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-06'),
        ]);

        // 20日後に期限切れ
        $student30days = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student30days->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-21'),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.expiring', [
            'course_id' => $course->id,
            'thresholds' => [3, 7, 30],
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);

        $this->assertEquals(3, $data[0]['days']);
        $this->assertCount(1, $data[0]['students']);
        $this->assertEquals($student3days->id, $data[0]['students'][0]['student_id']);

        $this->assertEquals(7, $data[1]['days']);
        $this->assertCount(1, $data[1]['students']);
        $this->assertEquals($student7days->id, $data[1]['students'][0]['student_id']);

        $this->assertEquals(30, $data[2]['days']);
        $this->assertCount(1, $data[2]['students']);
        $this->assertEquals($student30days->id, $data[2]['students'][0]['student_id']);
    }

    /** AC-ANALYTICS-031・AC-ANALYTICS-032 */
    #[DataProvider('excludedDeadlines')]
    public function test_受講期限がない・切れている・当日の受講生はどの区間にも含めない(?string $deadline, array $thresholds): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        $student = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => $deadline === null ? null : CarbonImmutable::parse($deadline),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.expiring', [
            'course_id' => $course->id,
            'thresholds' => $thresholds,
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertCount(0, $data[0]['students']);
    }

    /** AC-ANALYTICS-028 */
    public function test_受講期限がちょうど指定した日数後の受講生は含める(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
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
        $response = $this->getJson(route('instructor.courses.attendances.expiring', [
            'course_id' => $course->id,
            'thresholds' => [3],
        ]));

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data[0]['students']);
        $this->assertEquals($student->id, $data[0]['students'][0]['student_id']);
    }

    /** AC-ANALYTICS-033 */
    public function test_近日期限切れの受講生に氏名と受講期限日と期限までの残り日数が含まれる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
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
        $response = $this->getJson(route('instructor.courses.attendances.expiring', [
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
    }

    public function test_受講期限が近い順に並ぶ(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
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
        $response = $this->getJson(route('instructor.courses.attendances.expiring', [
            'course_id' => $course->id,
            'thresholds' => [30],
        ]));

        // Assert
        $response->assertStatus(200);
        $students = $response->json('data.0.students');
        $this->assertCount(2, $students);
        $this->assertEquals($studentSooner->id, $students[0]['student_id']);
        $this->assertEquals($studentLater->id, $students[1]['student_id']);
    }

    public function test_ほかの講座の受講生は含めない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $otherCourse = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        $studentInCourse = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $studentInCourse->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-05'),
        ]);

        $studentInOther = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $studentInOther->id,
            'course_id' => $otherCourse->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-05'),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.expiring', [
            'course_id' => $course->id,
            'thresholds' => [30],
        ]));

        // Assert
        $response->assertStatus(200);
        $students = $response->json('data.0.students');
        $this->assertCount(1, $students);
        $this->assertEquals($studentInCourse->id, $students[0]['student_id']);
    }

    /** AC-AUTHZ-001 */
    public function test_担当ではない講師の講座は取得できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.expiring', [
            'course_id' => $course->id,
            'thresholds' => [30],
        ]));

        // Assert
        $response->assertStatus(403);
    }

    /**
     * AC-ANALYTICS-029
     *
     * @param  array<string, list<int>>  $query
     */
    #[DataProvider('invalidInputs')]
    public function test_日数の指定または講座が不正だと422になる(bool $courseExists, array $query, string $errorKey): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $courseId = $courseExists
            ? Course::factory()->create(['instructor_id' => $instructor->id])->id
            : 99999;
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.expiring', [
            'course_id' => $courseId,
            ...$query,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([$errorKey]);
    }
}
