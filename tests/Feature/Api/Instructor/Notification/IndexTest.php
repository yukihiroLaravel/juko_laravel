<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_お知らせ一覧取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notifications.index'));

        // Assert
        $response->assertStatus(200);
    }

    public function test_定員あり講座_受講者数が返却される(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);
        Attendance::factory()->count(3)->create(['course_id' => $course->id]);
        Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notification.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.course.capacity', 10);
        $response->assertJsonPath('data.0.course.current_attendance_count', 3);
    }

    public function test_定員なし講座_受講者数がnullで返却される(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => null,
        ]);
        Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notification.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.course.capacity', null);
        $response->assertJsonPath('data.0.course.current_attendance_count', null);
    }
}
