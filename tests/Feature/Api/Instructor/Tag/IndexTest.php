<?php

namespace Tests\Feature\Api\Instructor\Tag;

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

    public function test_タグ一覧取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/instructor/tag/index');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }
}
