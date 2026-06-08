<?php

namespace Tests\Feature\Api\Manager\Course;

use App\Enums\Course\StatusEnum;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutStatusTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_スコープ外の講座は一括更新の対象外(): void
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

    public function test_バリデーションエラー_statusが未送信(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.courses.put-status'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_バリデーションエラー_statusが文字列でない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.courses.put-status'), [
            'status' => 123,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_バリデーションエラー_statusが下書き(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.courses.put-status'), [
            'status' => StatusEnum::DRAFT->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_バリデーションエラー_statusが許容外の文字列(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.courses.put-status'), [
            'status' => 'invalid_status',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
