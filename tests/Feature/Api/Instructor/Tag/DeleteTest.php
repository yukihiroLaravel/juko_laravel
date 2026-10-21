<?php

namespace Tests\Feature\Api\Instructor\Tag;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    /** AC-TAG-006 */
    #[DataProvider('instructorTypeProvider')]
    public function test_講座に付いていないタグを削除できる(string $type): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => $type]);
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.tags.delete', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseMissing('tags', [
            'id' => $tag->id,
        ]);
    }

    /** AC-TAG-005 */
    public function test_講座に付いているタグは削除できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->tags()->attach($tag->id);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.tags.delete', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, this tag is linked to courses.',
        ]);
    }

    /** AC-AUTHZ-005 */
    public function test_他の講師のタグは削除できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.tags.delete', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_タグの指定が数値でないときは削除できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.tags.delete', ['tag_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'tag_id',
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function instructorTypeProvider(): array
    {
        return [
            'マネージャー' => ['manager'],
            '講師' => ['instructor'],
        ];
    }
}
