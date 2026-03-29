<?php

namespace Tests\Feature\Api\Manager\Tag;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_タグ削除_成功(): void
    {
        // Arrange — 講座に紐づかないタグ
        $manager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.tags.delete', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_タグに紐づく講座が存在_失敗(): void
    {
        // Arrange — 講座に紐づくタグ
        $manager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $manager->id]);
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $course->tags()->attach($tag->id);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.tags.delete', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, this tag is linked to courses.',
        ]);
    }

    public function test_権限がないマネージャーで認証_失敗(): void
    {
        // Arrange — 別のマネージャーが所有するタグ
        $ownerManager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $ownerManager->id]);
        $otherManager = Instructor::factory()->create();
        $this->actingAs($otherManager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.tags.delete', ['tag_id' => $tag->id]));

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
        $response = $this->deleteJson(route('manager.tags.delete', ['tag_id' => $tag->id]));

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
        $response = $this->deleteJson(route('manager.tags.delete', ['tag_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'tag_id',
        ]);
    }
}
