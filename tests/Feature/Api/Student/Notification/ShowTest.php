<?php

namespace Tests\Feature\Api\Student\Notification;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    /** AC-NOTIF-018 */
    public function test_お知らせ詳細に講師名が含まれる(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create(['nick_name' => 'Alice']);
        $notification = $this->createNotificationOfEnrolledCourse($student, [], $instructor);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.show', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.instructor_nick_name', 'Alice');
    }

    /** AC-NOTIF-021 */
    public function test_受講期限を過ぎた受講生はお知らせの詳細を開けない(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $notification = $this->createNotificationOfEnrolledCourse($student, [
            'attendance_deadline' => now()->subDay(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.show', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'The course has expired.',
        ]);
    }

    /**
     * 生徒が受講している講座の、公開されているお知らせを作る。
     *
     * @param  array<string, mixed>  $attendanceAttributes
     */
    private function createNotificationOfEnrolledCourse(
        Student $student,
        array $attendanceAttributes = [],
        ?Instructor $instructor = null,
    ): Notification {
        $course = Course::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            ...$attendanceAttributes,
        ]);

        return Notification::factory()->create([
            'course_id' => $course->id,
            ...($instructor ? ['instructor_id' => $instructor->id] : []),
        ]);
    }
}
