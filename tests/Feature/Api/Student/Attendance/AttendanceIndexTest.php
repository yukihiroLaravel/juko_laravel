<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_index_is_paginated()
    {
        // Arrange — 1人の生徒が7つの講座を受講
        $student = Student::factory()->create();
        $courses = Course::factory()->count(7)->create();

        $courses->each(function ($course) use ($student) {
            Attendance::factory()->create([
                'student_id' => $student->id,
                'course_id' => $course->id,
            ]);
        });

        // Act
        $response = $this->actingAs($student)->getJson(route('student.attendance.index', ['per_page' => 6, 'page' => 1]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'links' => [
                'first', 'last', 'prev', 'next',
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total',
            ],
        ]);
        $response->assertJsonCount(6, 'data');
        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertEquals(2, $response->json('meta.last_page'));
    }
}
