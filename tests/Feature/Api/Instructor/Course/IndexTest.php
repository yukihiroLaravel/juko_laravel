<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_講座一覧取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        Course::factory()->count(2)->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_パラメータ指定_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $manager->id]);
        $taggedCourse = Course::factory()->create(['instructor_id' => $manager->id]);
        $taggedCourse->tags()->attach($tag->id);
        Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index', ['per_page' => 5, 'tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_タイトル検索_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        Course::factory()->create(['instructor_id' => $manager->id, 'title' => 'PHP入門講座']);
        Course::factory()->create(['instructor_id' => $manager->id, 'title' => 'JavaScript入門講座']);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index', ['search_word' => 'PHP']));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_タグ名検索_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $manager->id, 'content' => 'バックエンド入門編']);
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $course->tags()->attach($tag->id);
        Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index', ['search_word' => 'バックエンド入門編']));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }
}
