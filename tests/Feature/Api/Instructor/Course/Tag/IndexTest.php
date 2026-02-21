<?php

namespace Tests\Feature\Api\Instructor\Course\Tag;

use App\Model\Course;
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
        $tag1 = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $tag2 = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $tag3 = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->tags()->attach([$tag1->id, $tag2->id, $tag3->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.tag.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_パラメータ指定_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->tags()->attach($tag->id);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.course.tag.index', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }
}
