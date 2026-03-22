<?php

namespace Tests\Feature\Api\Manager\Tag;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_タグ一覧取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        // マネージャー自身のタグ3つ + 配下講師のタグ3つ = 6つ
        Tag::factory()->count(3)->create(['instructor_id' => $manager->id]);
        Tag::factory()->count(3)->create(['instructor_id' => $subordinate->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.course.tag.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(6, 'data');
    }

    public function test_パラメータ指定_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $manager->id]);
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $course->tags()->attach($tag->id);
        Tag::factory()->count(2)->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.course.tag.index', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_権限エラー(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.course.tag.index'));

        // Assert
        $response->assertStatus(403);
    }
}
