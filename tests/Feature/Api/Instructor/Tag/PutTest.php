<?php

namespace Tests\Feature\Api\Instructor\Tag;

use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PutTest extends TestCase
{
    use RefreshDatabase;

    /** AC-TAG-004 */
    #[DataProvider('instructorTypeProvider')]
    public function test_タグの表記を変更できる(string $type): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => $type]);
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.tags.put', ['tag_id' => $tag->id]), [
            'content' => 'test',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'content' => 'test',
        ]);
    }

    /** AC-AUTHZ-005 */
    public function test_他の講師のタグは更新できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.tags.put', ['tag_id' => $tag->id]), [
            'content' => 'test',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_タグの指定が数値でなく表記も空のときは更新できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.tags.put', ['tag_id' => 'aaa']), []);

        // Assert
        $response->assertStatus(422);
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
