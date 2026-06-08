<?php

namespace Tests\Feature\Api\Manager\Course;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_講座一覧取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        Course::factory()->count(3)->create(['instructor_id' => $manager->id]);
        Course::factory()->count(3)->create(['instructor_id' => $subordinate->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(6, 'data');
    }

    public function test_パラメータ指定_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        Course::factory()->count(5)->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.index', ['per_page' => 3]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_タグ指定_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $manager->id]);
        $taggedCourse = Course::factory()->create(['instructor_id' => $manager->id]);
        $taggedCourse->tags()->attach($tag->id);
        Course::factory()->count(2)->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.index', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }
}
