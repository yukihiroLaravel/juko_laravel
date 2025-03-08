<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_講座一覧取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/instructor/course/index');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_パラメータ指定_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');
        Course::find(5)->delete();

        // act
        $response = $this->getJson('/api/v1/instructor/course/index?per_page=5&tag_id=1');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_タイトル検索_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');
        Course::find(5)->delete();

        // act
        $response = $this->getJson('/api/v1/instructor/course/index?search_word=PHP');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }
}
