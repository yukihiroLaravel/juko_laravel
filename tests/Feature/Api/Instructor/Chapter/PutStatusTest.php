<?php

namespace Tests\Feature\Api\Instructor\Chapter;

use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PutStatusTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-022 */
    public function test_講座の全チャプターの公開状態をまとめて変更できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapters = Chapter::factory()->count(2)->create(['course_id' => $course->id, 'status' => 'public']);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put-status', ['course_id' => $course->id]), [
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        foreach ($chapters as $chapter) {
            $this->assertDatabaseHas('chapters', [
                'id' => $chapter->id,
                'status' => 'private',
            ]);
        }
    }

    /**
     * 講座のチャプターの公開状態の組み合わせと、すべてが変更を許される変更先
     *
     * @return array<string, array{list<string>, string}>
     */
    public static function allowedBulkTransitions(): array
    {
        return [
            '下書きと非公開を公開にする' => [['draft', 'private'], 'public'],
            '公開と非公開を非公開にする' => [['public', 'private'], 'private'],
        ];
    }

    /**
     * AC-AUTHOR-022
     *
     * @param  list<string>  $currentStatuses
     */
    #[DataProvider('allowedBulkTransitions')]
    public function test_公開状態の違うチャプターもまとめて変更できる(array $currentStatuses, string $targetStatus): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapters = collect($currentStatuses)->map(
            fn (string $status) => Chapter::factory()->create(['course_id' => $course->id, 'status' => $status])
        );
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put-status', ['course_id' => $course->id]), [
            'status' => $targetStatus,
        ]);

        // Assert
        $response->assertStatus(200);
        foreach ($chapters as $chapter) {
            $this->assertDatabaseHas('chapters', [
                'id' => $chapter->id,
                'status' => $targetStatus,
            ]);
        }
    }

    /** AC-AUTHOR-022・AC-COMMON-014 */
    public function test_下書きのチャプターを含む講座は非公開にできず全チャプターが元の公開状態のまま(): void
    {
        // Arrange — 公開から非公開は許されるが、下書きから非公開は許されない
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $publicChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => 'public']);
        $draftChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => 'draft']);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put-status', ['course_id' => $course->id]), [
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(422);
        $this->assertDatabaseHas('chapters', [
            'id' => $publicChapter->id,
            'status' => 'public',
        ]);
        $this->assertDatabaseHas('chapters', [
            'id' => $draftChapter->id,
            'status' => 'draft',
        ]);
    }

    /** AC-AUTHZ-001 */
    public function test_他の講師の講座のチャプターの公開状態は変更できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        Chapter::factory()->create(['course_id' => $course->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put-status', ['course_id' => $course->id]), [
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_講座と公開状態の指定が不正な場合は変更できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put-status', ['course_id' => 'aaa']), [
            'status' => 'string',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'status',
        ]);
    }

    public function test_公開状態に下書きは指定できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.chapters.put-status', ['course_id' => $course->id]), [
            'status' => 'draft',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
