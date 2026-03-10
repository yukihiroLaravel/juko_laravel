<?php

namespace Tests\Feature\Api\Instructor\Tag;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_タグ登録_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.tag.store'), [
            'content' => 'test',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('tags', [
            'instructor_id' => $instructor->id,
            'content' => 'test',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.tag.store'));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'content',
        ]);
    }
}
