<?php

namespace Tests\Feature\Api\Instructor\Tag;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_タグ削除_成功(): void
    {
        // Arrange — 講座に紐づかないタグ
        $instructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.tag.delete', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_タグに紐づく講座が存在_失敗(): void
    {
        // Arrange — 講座に紐づくタグ
        $instructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->tags()->attach($tag->id);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.tag.delete', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, this tag is linked to courses.',
        ]);
    }

    public function test_権限エラー(): void
    {
        // Arrange — 別の講師が所有するタグ
        $ownerInstructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.tag.delete', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.tag.delete', ['tag_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'tag_id',
        ]);
    }
}
