<?php

namespace Tests\Feature\Api\Manager\Tag;

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
        $manager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.tag.put', ['tag_id' => $tag->id]), [
            'content' => 'test',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'content' => 'test',
        ]);
    }

    public function test_権限がないマネージャーで認証_失敗(): void
    {
        // Arrange — 別のマネージャーが所有するタグには更新権限がない
        $ownerManager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $ownerManager->id]);
        $otherManager = Instructor::factory()->create();
        $this->actingAs($otherManager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.tag.put', ['tag_id' => $tag->id]), [
            'content' => 'test',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_権限エラー(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $tag = Tag::factory()->create();
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.tag.put', ['tag_id' => $tag->id]), [
            'content' => 'test',
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
        $response = $this->putJson(route('manager.tag.put', ['tag_id' => 'aaa']), [
            'content' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'tag_id',
            'content',
        ]);
    }
}
