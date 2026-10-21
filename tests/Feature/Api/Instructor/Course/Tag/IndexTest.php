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

    /** AC-TAG-007 */
    public function test_自分のタグだけが一覧に返る(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        Tag::factory()->count(3)->create(['instructor_id' => $instructor->id]);
        // 他の講師のタグは一覧に含まれない
        Tag::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.tags.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    /** AC-TAG-008 */
    public function test_タグを指定すると_そのタグだけが返る(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->tags()->attach($tag->id);
        $otherTag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $otherCourse = Course::factory()->create(['instructor_id' => $instructor->id]);
        $otherCourse->tags()->attach($otherTag->id);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.tags.index', ['tag_id' => $tag->id]));

        // Assert — 指定したタグだけが返り、ほかのタグは含まれない
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.tag_id', $tag->id);
    }
}
