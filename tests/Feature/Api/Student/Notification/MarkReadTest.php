<?php

namespace Tests\Feature\Api\Student\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Notification;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_お知らせ既読登録できる(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $notification = $this->createNotificationOfEnrolledCourse($student);
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
    public function test_同じお知らせへの確認を繰り返しても成功し記録は1件である(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $notification = $this->createNotificationOfEnrolledCourse($student);
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

    /** AC-NOTIF-025 */
    public function test_受講期限を過ぎた受講生はお知らせを確認済みにできない(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $notification = $this->createNotificationOfEnrolledCourse($student, [
            'attendance_deadline' => now()->subDay(),
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

    /**
     * 生徒が受講している講座の、一度きりのお知らせを作る。
     *
     * @param  array<string, mixed>  $attendanceAttributes
     */
    private function createNotificationOfEnrolledCourse(Student $student, array $attendanceAttributes = []): Notification
    {
        $course = Course::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            ...$attendanceAttributes,
        ]);

        return Notification::factory()->create([
            'course_id' => $course->id,
            'type' => TypeEnum::ONCE,
        ]);
    }
}
