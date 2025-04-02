<?php

namespace Tests\Feature\Api\Instructor\Tag;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_タグ登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/tag', [
            'content' => 'test',
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('tags', [
            'instructor_id' => $instructor->id,
            'content' => 'test',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/tag');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'content',
        ]);
    }
}
