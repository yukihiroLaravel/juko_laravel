<?php

namespace Tests\Feature\Api\Manager\Tag;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_タグ一覧取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/course/tag/index');

        // assert
        $response->assertStatus(200);
        // $response->assertJsonCount(3, 'data');
    }

    public function test_パラメータ指定_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/course/tag/index?tag_id=1');

        // assert
        $response->assertStatus(200);
        // $response->assertJsonCount(2, 'data');
    }

    // public function test_権限エラー(): void
    // {
    //     // arrange
    //     $instructor = Instructor::find(2);
    //     $this->actingAs($instructor, 'instructor');

    //     // act
    //     $response = $this->getJson('/api/v1/manager/tag/1', [
    //         'content' => 'test',
    //     ]);

    //     // assert
    //     $response->assertStatus(403);
    // }

    // public function test_バリデーションエラー(): void
    // {
    //     // arrange
    //     $instructor = Instructor::find(1);
    //     $this->actingAs($instructor, 'instructor');

    //     // act
    //     $response = $this->getJson('/api/v1/manager/tag/aaa', [
    //         'content' => '',
    //     ]);

    //     // assert
    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors([
    //         'tag_id',
    //         'content',
    //     ]);
    // }
}
