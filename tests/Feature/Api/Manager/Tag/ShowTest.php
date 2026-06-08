<?php

namespace Tests\Feature\Api\Manager\Tag;

use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_タグ詳細取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.tags.show', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'tag_id' => $tag->id,
                'content' => $tag->content,
            ],
        ]);
    }

    public function test_権限エラー(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $tag = Tag::factory()->create();
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.tags.show', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_存在しないタグ_id(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.tags.show', ['tag_id' => 9999]));

        // Assert
        $response->assertStatus(422);
    }

    public function test_不正なタグ_id形式(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.tags.show', ['tag_id' => 'abc']));

        // Assert
        $response->assertStatus(422);
    }
}
