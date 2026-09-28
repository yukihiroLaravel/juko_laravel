<?php

namespace Tests\Feature\Api\Manager\Course;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    /** AC-COMMON-007, AC-COMMON-008, AC-AUTHZ-002 */
    #[DataProvider('searchWordProvider')]
    public function test_本人と配下の講座を講座名またはタグ名の部分一致で検索できる(string $searchWord): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        $outsider = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $ownCourse = Course::factory()->create(['instructor_id' => $manager->id, 'title' => '基礎講座入門']);
        $subordinateCourse = Course::factory()->create([
            'instructor_id' => $subordinate->id, 'title' => '基礎講座入門',
        ]);
        $tag = Tag::factory()->create(['instructor_id' => $manager->id, 'content' => '基礎講座入門']);
        $taggedCourse = Course::factory()->create(['instructor_id' => $manager->id, 'title' => '応用編']);
        $taggedCourse->tags()->attach($tag);
        $bothCourse = Course::factory()->create(['instructor_id' => $manager->id, 'title' => '基礎講座入門']);
        $bothCourse->tags()->attach($tag);
        Course::factory()->create(['instructor_id' => $manager->id, 'title' => '対象外']);
        Course::factory()->create(['instructor_id' => $outsider->id, 'title' => '基礎講座入門']);
        $outsiderCourse = Course::factory()->create(['instructor_id' => $outsider->id, 'title' => '対象外']);
        $outsiderTag = Tag::factory()->create(['instructor_id' => $outsider->id, 'content' => '基礎講座入門']);
        $outsiderCourse->tags()->attach($outsiderTag);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.index', ['search_word' => $searchWord]));

        // Assert
        $response->assertOk();
        $this->assertSame(
            [$ownCourse->id, $subordinateCourse->id, $taggedCourse->id, $bothCourse->id],
            array_column($response->json('data'), 'course_id')
        );
        $response->assertJsonPath('meta.total', 4);
    }

    /** @return array<string, array{string}> */
    public static function searchWordProvider(): array
    {
        return [
            '先頭に一致' => ['基礎'],
            '途中に一致' => ['講座'],
            '末尾に一致' => ['入門'],
            '全体に一致' => ['基礎講座入門'],
        ];
    }

    /** AC-COMMON-008 */
    public function test_講座名またはタグ名が一致しても指定タグのない講座は含まれない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $selectedTag = Tag::factory()->create(['instructor_id' => $manager->id, 'content' => '選択タグ']);
        $matchingTag = Tag::factory()->create(['instructor_id' => $manager->id, 'content' => '基礎講座入門']);
        $titleMatch = Course::factory()->create(['instructor_id' => $manager->id, 'title' => '基礎講座入門']);
        $titleMatch->tags()->attach($selectedTag);
        $tagMatch = Course::factory()->create(['instructor_id' => $manager->id, 'title' => '応用編']);
        $tagMatch->tags()->attach([$selectedTag->id, $matchingTag->id]);
        Course::factory()->create(['instructor_id' => $manager->id, 'title' => '基礎講座入門']);
        $withoutSelectedTag = Course::factory()->create(['instructor_id' => $manager->id, 'title' => '応用編']);
        $withoutSelectedTag->tags()->attach($matchingTag);
        $withoutKeyword = Course::factory()->create(['instructor_id' => $manager->id, 'title' => '対象外']);
        $withoutKeyword->tags()->attach($selectedTag);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.index', [
            'search_word' => '講座', 'tag_id' => $selectedTag->id,
        ]));

        // Assert
        $response->assertOk();
        $this->assertSame([$titleMatch->id, $tagMatch->id], array_column($response->json('data'), 'course_id'));
        $response->assertJsonPath('meta.total', 2);
    }

    /** AC-COMMON-011 */
    public function test_講座名にもタグ名にも一致しない場合は空の一覧を返す(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id, 'title' => '基礎講座']);
        $tag = Tag::factory()->create(['instructor_id' => $manager->id, 'content' => '入門']);
        $course->tags()->attach($tag);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.index', ['search_word' => '該当なし']));

        // Assert
        $response->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 0);
    }

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
