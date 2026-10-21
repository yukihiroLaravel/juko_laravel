<?php

namespace Tests\Feature\Api\Instructor\Tag;

use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    /** AC-TAG-007 */
    public function test_自分のタグの一覧を取得できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        Tag::factory()->count(3)->create(['instructor_id' => $instructor->id]);
        Tag::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.tags.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }
}
