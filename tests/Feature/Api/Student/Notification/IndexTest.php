<?php

namespace Tests\Feature\Api\Student\Notification;

use App\Enums\Course\DeadlineTypeEnum;
use App\Enums\Notification\StatusEnum;
use App\Enums\Notification\TypeEnum;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_お知らせ一覧取得_成功(): void
    {
        // Arrange — 生徒が受講している講座に公開お知らせ2件
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        Notification::factory()->count(2)->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.notifications');
    }

    public function test_固定期限切れのお知らせは取得しない(): void
    {
        // Arrange — 固定期限切れの受講
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE->value,
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => now()->subDay(),
        ]);
        Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data.notifications');
    }

    public function test_相対期限切れのお知らせは取得しない(): void
    {
        // Arrange — 相対期限切れの受講
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => now()->subDay(),
        ]);
        Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data.notifications');
    }

    public function test_期限がない講座のお知らせは取得する(): void
    {
        // Arrange — 期限なしの講座
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        Notification::factory()->count(2)->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.notifications');
    }

    public function test_お知らせ一覧に講師名が含まれる(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create(['nick_name' => 'Alice']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notification.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.notifications.0.instructor_nick_name', 'Alice');
    }

    public function test_講師名昇順でソートできる(): void
    {
        // Arrange — 講師2名のお知らせを作成
        $student = Student::factory()->create();
        $instructorB = Instructor::factory()->create(['nick_name' => 'Bob']);
        $instructorA = Instructor::factory()->create(['nick_name' => 'Alice']);
        $courseB = Course::factory()->create([
            'instructor_id' => $instructorB->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
        $courseA = Course::factory()->create([
            'instructor_id' => $instructorA->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $courseB->id,
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $courseA->id,
        ]);
        Notification::factory()->create([
            'course_id' => $courseB->id,
            'instructor_id' => $instructorB->id,
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        Notification::factory()->create([
            'course_id' => $courseA->id,
            'instructor_id' => $instructorA->id,
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notification.index', [
            'sort_by' => 'instructor_nick_name',
            'order' => 'asc',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.notifications.0.instructor_nick_name', 'Alice');
        $response->assertJsonPath('data.notifications.1.instructor_nick_name', 'Bob');
    }

    public function test_講師名降順でソートできる(): void
    {
        // Arrange — 講師2名のお知らせを作成
        $student = Student::factory()->create();
        $instructorA = Instructor::factory()->create(['nick_name' => 'Alice']);
        $instructorB = Instructor::factory()->create(['nick_name' => 'Bob']);
        $courseA = Course::factory()->create([
            'instructor_id' => $instructorA->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
        $courseB = Course::factory()->create([
            'instructor_id' => $instructorB->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $courseA->id,
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $courseB->id,
        ]);
        Notification::factory()->create([
            'course_id' => $courseA->id,
            'instructor_id' => $instructorA->id,
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        Notification::factory()->create([
            'course_id' => $courseB->id,
            'instructor_id' => $instructorB->id,
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notification.index', [
            'sort_by' => 'instructor_nick_name',
            'order' => 'desc',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.notifications.0.instructor_nick_name', 'Bob');
        $response->assertJsonPath('data.notifications.1.instructor_nick_name', 'Alice');
    }

    public function test_不正なソート項目はバリデーションエラーになる(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notification.index', [
            'sort_by' => 'invalid_sort_key',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sort_by']);
    }
}
