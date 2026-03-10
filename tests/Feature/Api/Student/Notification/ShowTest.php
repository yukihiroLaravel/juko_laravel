<?php

namespace Tests\Feature\Api\Student\Notification;

use App\Enums\Notification\StatusEnum;
use App\Enums\Notification\TypeEnum;
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

    public function test_お知らせ取得できる(): void
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
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notification.show', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_講座の期限切れは403になる(): void
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
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => now()->subDay(),
            'end_date' => now()->addWeek(),
        ]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notification.show', ['notification_id' => $notification->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'The course has expired.',
        ]);
    }
}
