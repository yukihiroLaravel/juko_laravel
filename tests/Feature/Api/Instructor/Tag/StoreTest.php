<?php

namespace Tests\Feature\Api\Instructor\Tag;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    /** AC-TAG-001 */
    public function test_タグを自分のタグとして登録できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.tags.store'), [
            'content' => 'test',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('tags', [
            'instructor_id' => $instructor->id,
            'content' => 'test',
        ]);
    }

    public function test_タグの表記が空のときは登録できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.tags.store'));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'content',
        ]);
    }
}
