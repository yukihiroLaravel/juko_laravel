<?php

namespace Tests\Feature\Api\Manager\Tag;

use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    /** AC-TAG-011 */
    public function test_自分のタグの詳細を取得できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.tags.show', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'tag_id' => $tag->id,
                'content' => $tag->content,
            ],
        ]);
    }

    public function test_マネージャーでない講師はタグの詳細を取得できない(): void
    {
        // Arrange
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.tags.show', ['tag_id' => 1]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    #[DataProvider('invalidTagIdProvider')]
    public function test_存在しないタグや形式の不正なタグを指定すると入力エラーになる(int|string $tagId): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.tags.show', ['tag_id' => $tagId]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tag_id']);
    }

    /** @return array<string, array{int|string}> */
    public static function invalidTagIdProvider(): array
    {
        return [
            '存在しないタグ' => [9999],
            '数字でないタグの指定' => ['abc'],
        ];
    }
}
