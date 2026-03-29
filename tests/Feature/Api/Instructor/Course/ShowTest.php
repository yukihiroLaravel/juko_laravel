<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use App\Model\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_講座取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'deadline_type' => 'none',
        ]);
        CourseDeadline::factory()->create(['course_id' => $course->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.show', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'course_id',
                'title',
                'image',
                'status',
                'deadline_type',
                'course_deadline' => [
                    'course_deadline_id',
                    'fixed_date',
                    'relative_days',
                ],
                'chapters' => [
                    '*' => [
                        'chapter_id',
                        'title',
                        'order',
                        'status',
                        'lessons' => [
                            '*' => [
                                'lesson_id',
                                'title',
                                'remarks',
                                'status',
                                'order',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // Arrange — 別の講師が所有する講座
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.show', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }
}
