<?php

namespace Tests\Feature\Api\Manager\Tag;

use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutTest extends TestCase
{
    use RefreshDatabase;

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

        $course = Course::find(1);

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
    }

    // public function test_バリデーションエラー(): void
    // {
    //     // arrange
    //     $instructor = Instructor::find(1);
    //     $this->actingAs($instructor, 'instructor');

    //     // act
    //     $response = $this->putJson('/api/v1/manager/tag/1', [
    //         'content' => '',
    //     ]);

    //     // assert
    //     $response->assertStatus(422);
    //     $response->assertJsonValidationErrors([
    //         'content' => 'The content field is required.',
    //     ]);
    // }
}
