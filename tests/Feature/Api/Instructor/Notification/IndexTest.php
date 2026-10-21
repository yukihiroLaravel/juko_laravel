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

    /** AC-NOTIF-029 */
    public function test_定員ありの講座のお知らせには定員と受講中の受講生数が含まれる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
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
        $response = $this->getJson(route('instructor.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.course.capacity', 10);
        $response->assertJsonPath('data.0.course.current_attendance_count', 3);
    }

    public function test_定員なしの講座のお知らせは受講中の受講生数を返さない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
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
        $response = $this->getJson(route('instructor.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.course.capacity', null);
        $response->assertJsonPath('data.0.course.current_attendance_count', null);
    }
}
