<?php

namespace Tests\Feature\Api\Manager\Tag;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_タグ削除_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/manager/tag/7');

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseMissing('tags', [
            'id' => 7,
        ]);
    }

    public function test_タグに紐づく講座が存在_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/manager/tag/1');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, this tag is linked to courses.',
        ]);
    }

    public function test_権限がないマネージャーで認証_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/manager/tag/1');

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
        $response = $this->deleteJson('/api/v1/manager/tag/1');

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
        $response = $this->deleteJson('/api/v1/manager/tag/aaa');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'tag_id',
        ]);
    }
}
