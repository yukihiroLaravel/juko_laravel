<?php

namespace Tests\Feature\Api\Manager\Tag;

use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_タグ詳細取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        $tag = Tag::find(1);

        // act
        $response = $this->getJson('/api/v1/manager/tag/1');

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'tag_id' => 1,
                'content' => 'バックエンド入門編',
            ],
        ]);
    }

    public function test_権限エラー(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/tag/1');

        // assert
        $response->assertStatus(403);
    }

    public function test_存在しないタグ_id(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/tag/9999');

        // assert
        $response->assertStatus(422);
    }

    public function test_不正なタグ_id形式(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/tag/abc');

        // assert
        $response->assertStatus(422);
    }
}
