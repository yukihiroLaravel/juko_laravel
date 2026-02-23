<?php

namespace Tests\Feature\Api\Manager\Instructor\Course;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_講師講座一覧取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        Course::factory()->count(2)->create(['instructor_id' => $subordinate->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructor.course.index', ['instructor_id' => $subordinate->id]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_配下の講師でない_成功(): void
    {
        // Arrange — 配下ではない講師の講座一覧を取得しようとする
        $otherManager = Instructor::factory()->create();
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructor.course.index', ['instructor_id' => $instructor->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, invalid instructor_id.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructor.course.index', ['instructor_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'instructor_id',
        ]);
    }
}
