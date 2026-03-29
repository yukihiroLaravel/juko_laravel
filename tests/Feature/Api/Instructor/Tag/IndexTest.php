<?php

namespace Tests\Feature\Api\Instructor\Tag;

use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_タグ一覧取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        Tag::factory()->count(3)->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.tags.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }
}
