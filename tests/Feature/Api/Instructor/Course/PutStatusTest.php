<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Enums\Course\StatusEnum;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PutStatusTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-012 */
    public function test_講師で自分の講座のステータスを一括更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course1 = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => StatusEnum::PRIVATE->value,
        ]);
        $course2 = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-status'), [
            'courses' => [$course1->id, $course2->id],
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => true]);
        $this->assertDatabaseHas('courses', [
            'id' => $course1->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $course2->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
    }

    /** AC-AUTHOR-012 AC-AUTHZ-002 */
    public function test_マネージャーで配下の講師の講座を一括更新_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create([
            'instructor_id' => $subordinate->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-status'), [
            'courses' => [$course->id],
            'status' => StatusEnum::PRIVATE->value,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => true]);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => StatusEnum::PRIVATE->value,
        ]);
    }

    /** AC-AUTHOR-009 */
    public function test_状態遷移バリデーションエラー_下書きから非公開へ更新できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => StatusEnum::DRAFT->value,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-status'), [
            'courses' => [$course->id],
            'status' => StatusEnum::PRIVATE->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => StatusEnum::DRAFT->value,
        ]);
    }

    /** AC-AUTHZ-003 */
    public function test_権限がないマネージャーの場合更新できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-status'), [
            'courses' => [$course->id],
            'status' => StatusEnum::PRIVATE->value,
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
    }

    /** AC-AUTHZ-001 */
    public function test_権限がない講師の場合更新できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-status'), [
            'courses' => [$course->id],
            'status' => StatusEnum::PRIVATE->value,
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    /** AC-COMMON-016 */
    public function test_一部に権限がない講座を含む場合更新できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $ownCourse = Course::factory()->create([
            'instructor_id' => $manager->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $otherCourse = Course::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-status'), [
            'courses' => [$ownCourse->id, $otherCourse->id],
            'status' => StatusEnum::PRIVATE->value,
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $ownCourse->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('講座の指定が不正な入力')]
    public function test_講座の指定が不正だと一括更新できない(array $input, string $errorKey): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-status'), [
            'status' => StatusEnum::PUBLIC->value,
            ...$input,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([$errorKey]);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function 講座の指定が不正な入力(): array
    {
        return [
            '講座の指定がない' => [[], 'courses'],
            '講座の指定が空' => [['courses' => []], 'courses'],
            '講座の指定が配列でない' => [['courses' => 'abc'], 'courses'],
            '講座の指定が数値でない' => [['courses' => ['abc']], 'courses.0'],
            '存在しない講座を指定' => [['courses' => [99999]], 'courses.0'],
        ];
    }

    public function test_削除済みの講座は一括更新できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-status'), [
            'courses' => [$course->id],
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('公開状態が不正な入力')]
    public function test_公開状態が不正だと一括更新できない(array $input): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.courses.put-status'), [
            'courses' => [$course->id],
            ...$input,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function 公開状態が不正な入力(): array
    {
        return [
            '公開状態の指定がない' => [[]],
            '公開状態が文字列でない' => [['status' => 123]],
            '公開状態が下書き' => [['status' => StatusEnum::DRAFT->value]],
        ];
    }
}
