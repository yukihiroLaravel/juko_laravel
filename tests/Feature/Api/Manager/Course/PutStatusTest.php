<?php

namespace Tests\Feature\Api\Manager\Course;

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

    /** AC-AUTHOR-013・AC-AUTHOR-014 */
    public function test_マネージャーで自分の複数講座のステータスを一括更新_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course1 = Course::factory()->create([
            'instructor_id' => $manager->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $course2 = Course::factory()->create([
            'instructor_id' => $manager->id,
            'status' => StatusEnum::PRIVATE->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.courses.put-status'), [
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => 'true']);
        $this->assertEqualsCanonicalizing(
            [$course1->id, $course2->id],
            $response->json('updated_ids')
        );
        $this->assertDatabaseHas('courses', [
            'id' => $course1->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $course2->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
    }

    /** AC-AUTHOR-013・AC-AUTHOR-014・AC-AUTHZ-002 */
    public function test_マネージャーで配下の講師の講座も一括更新_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $managerCourse = Course::factory()->create([
            'instructor_id' => $manager->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $subordinateCourse = Course::factory()->create([
            'instructor_id' => $subordinate->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.courses.put-status'), [
            'status' => StatusEnum::PRIVATE->value,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => 'true']);
        $this->assertEqualsCanonicalizing(
            [$managerCourse->id, $subordinateCourse->id],
            $response->json('updated_ids')
        );
        $this->assertDatabaseHas('courses', [
            'id' => $managerCourse->id,
            'status' => StatusEnum::PRIVATE->value,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $subordinateCourse->id,
            'status' => StatusEnum::PRIVATE->value,
        ]);
    }

    /** AC-AUTHOR-013 */
    public function test_下書きの講座は一括更新の対象外(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $publicCourse = Course::factory()->create([
            'instructor_id' => $manager->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $draftCourse = Course::factory()->create([
            'instructor_id' => $manager->id,
            'status' => StatusEnum::DRAFT->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.courses.put-status'), [
            'status' => StatusEnum::PRIVATE->value,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => 'true']);
        $this->assertEqualsCanonicalizing(
            [$publicCourse->id],
            $response->json('updated_ids')
        );
        $this->assertDatabaseHas('courses', [
            'id' => $publicCourse->id,
            'status' => StatusEnum::PRIVATE->value,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $draftCourse->id,
            'status' => StatusEnum::DRAFT->value,
        ]);
    }

    /** AC-AUTHOR-013 */
    public function test_配下でない講師の講座は一括更新の対象外(): void
    {
        // Arrange
        $ownerManager = Instructor::factory()->create();
        $otherManager = Instructor::factory()->create();
        $ownerCourse = Course::factory()->create([
            'instructor_id' => $ownerManager->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($otherManager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.courses.put-status'), [
            'status' => StatusEnum::PRIVATE->value,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => 'true',
            'updated_ids' => [],
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $ownerCourse->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
    }

    public function test_マネージャー権限がない講師は更新できない(): void
    {
        // Arrange
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.courses.put-status'), [
            'status' => StatusEnum::PRIVATE->value,
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    /**
     * 公開状態に指定できない値。下書きの扱いは仕様の確認待ちのため、AC-ID は付けていない
     *
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidRequests(): array
    {
        return [
            '公開状態が未送信' => [[]],
            '公開状態が数値' => [['status' => 123]],
            '公開状態が下書き' => [['status' => StatusEnum::DRAFT->value]],
            '公開状態が存在しない値' => [['status' => 'invalid_status']],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidRequests')]
    public function test_公開状態が未送信か不正な値だと一括更新できない(array $payload): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.courses.put-status'), $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
