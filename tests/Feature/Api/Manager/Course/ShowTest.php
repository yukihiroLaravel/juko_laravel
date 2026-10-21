<?php

namespace Tests\Feature\Api\Manager\Course;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_講座取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.show', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'course_id',
                'title',
                'image',
                'status',
                'deadline_type',
                'course_deadline',
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

    /** AC-AUTHZ-002 */
    public function test_配下の講師の講座をマネージャーが取得できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.show', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.course_id', $course->id);
    }

    /** AC-AUTHZ-003 */
    public function test_配下でないマネージャーの講座は取得できない(): void
    {
        // Arrange
        $otherManager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherManager->id]);
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.show', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_講座の識別子が数値でないと取得できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.show', ['course_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }
}
