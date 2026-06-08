<?php

namespace Tests\Feature\Api\Instructor\Tag;

use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutTest extends TestCase
{
    use RefreshDatabase;

    public function test_タグ更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.tags.put', ['tag_id' => $tag->id]), [
            'content' => 'test',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'content' => 'test',
        ]);
    }

    public function test_許可がない講師_失敗(): void
    {
        // Arrange — 別の講師が所有するタグには更新権限がない
        $ownerInstructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.tags.put', ['tag_id' => $tag->id]), [
            'content' => 'test',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.tags.put', ['tag_id' => 'aaa']), []);

        // Assert
        $response->assertStatus(422);
    }
}
