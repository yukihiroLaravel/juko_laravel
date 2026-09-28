<?php

namespace Tests\Feature\Api\Student\Notification;

use App\Enums\Notification\StatusEnum;
use App\Enums\Notification\TypeEnum;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use App\Model\Student;
use App\Model\ViewedOnceNotification;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_お知らせ既読登録できる(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $notification = Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => '確認済みのお知らせ',
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->postJson(
            route('student.notifications.mark-read'),
            ['notification_id' => $notification->id]
        );

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('viewed_once_notifications', [
            'notification_id' => $notification->id,
            'student_id' => $student->id,
        ]);
    }

    // AC-NOTIF-026
    public function test_同じお知らせの確認済み記録を二重に保存できない(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $notification = Notification::factory()->create(['title' => '確認済みのお知らせ']);
        ViewedOnceNotification::factory()->create([
            'notification_id' => $notification->id,
            'student_id' => $student->id,
        ]);

        $this->expectException(QueryException::class);

        // Act
        ViewedOnceNotification::factory()->create([
            'notification_id' => $notification->id,
            'student_id' => $student->id,
        ]);

        // Assert
    }

    // AC-NOTIF-026
    public function test_同じお知らせへの確認を繰り返しても成功し記録は1件である(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $notification = Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => '確認済みのお知らせ',
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $firstResponse = $this->postJson(
            route('student.notifications.mark-read'),
            ['notification_id' => $notification->id]
        );
        $secondResponse = $this->postJson(
            route('student.notifications.mark-read'),
            ['notification_id' => $notification->id]
        );

        // Assert
        $firstResponse->assertStatus(200);
        $secondResponse->assertStatus(200);
        $this->assertDatabaseCount('viewed_once_notifications', 1);
    }

    public function test_期限切れのお知らせは既読登録できない(): void
    {
        // Arrange — 期限切れの受講
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => now()->subDays(1),
        ]);
        $notification = Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => '確認済みのお知らせ',
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->postJson(
            route('student.notifications.mark-read'),
            ['notification_id' => $notification->id]
        );

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'The course has expired.',
        ]);
        $this->assertDatabaseMissing('viewed_once_notifications', [
            'notification_id' => $notification->id,
            'student_id' => $student->id,
        ]);
    }
}
