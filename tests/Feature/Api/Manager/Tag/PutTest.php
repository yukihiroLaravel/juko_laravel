<?php

namespace Tests\Feature\Api\Manager\Tag;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_タグ更新_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/tag/1', [
            'content' => 'test',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('tags', [
            'id' => 1,
            'content' => 'test',
        ]);
    }

    public function test_権限がないマネージャーで認証_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/tag/1', [
            'content' => 'test',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_権限エラー(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/tag/1', [
            'content' => 'test',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/tag/aaa', [
            'content' => '',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'tag_id',
            'content',
        ]);
    }
}
