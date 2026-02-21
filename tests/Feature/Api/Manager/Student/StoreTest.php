<?php

namespace Tests\Feature\Api\Manager\Student;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_生徒取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.student.store'), [
            'given_name_by_instructor' => 'John',
            'email' => 'john@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('students', [
            'given_name_by_instructor' => 'John',
            'email' => 'john@example.com',
        ]);
    }

    public function test_権限エラー(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.student.store'), [
            'given_name_by_instructor' => 'John',
            'email' => 'john@example.com',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.student.store'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'given_name_by_instructor',
            'email',
        ]);
    }
}
