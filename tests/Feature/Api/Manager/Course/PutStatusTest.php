<?php

namespace Tests\Feature\Api\Manager\Course;

use App\Enums\Course\StatusEnum as CourseStatusEnum;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_複数講座ステータス一括更新_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course1 = Course::factory()->create([
            'instructor_id' => $manager->id,
            'status' => CourseStatusEnum::PUBLIC->value,
        ]);
        $course2 = Course::factory()->create([
            'instructor_id' => $manager->id,
            'status' => CourseStatusEnum::PRIVATE->value,
        ]);
        Course::factory()->create([
            'instructor_id' => $manager->id,
            'status' => CourseStatusEnum::DRAFT->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.course.put-status'), [
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => true]);
        $this->assertEqualsCanonicalizing(
            [$course1->id, $course2->id],
            $response->json('updated_ids')
        );
        $this->assertDatabaseHas('courses', [
            'id' => $course1->id,
            'status' => CourseStatusEnum::PUBLIC->value,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $course2->id,
            'status' => CourseStatusEnum::PUBLIC->value,
        ]);
    }

    public function test_配下講師の講座も一括更新される_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $instructor->id,
        ]);
        $managerCourse = Course::factory()->create([
            'instructor_id' => $manager->id,
            'status' => CourseStatusEnum::PUBLIC->value,
        ]);
        $subordinateCourse = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => CourseStatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.course.put-status'), [
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['result' => 'true']);
        $this->assertEqualsCanonicalizing(
            [$managerCourse->id, $subordinateCourse->id],
            $response->json('updated_ids')
        );
        $this->assertDatabaseHas('courses', [
            'id' => $subordinateCourse->id,
            'status' => CourseStatusEnum::PRIVATE->value,
        ]);
    }

    public function test_権限がないマネージャーが講座ステータス一括更新_失敗(): void
    {
        // Arrange
        $ownerManager = Instructor::factory()->create();
        $otherManager = Instructor::factory()->create();
        $ownerCourse = Course::factory()->create([
            'instructor_id' => $ownerManager->id,
            'status' => CourseStatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($otherManager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.course.put-status'), [
            'status' => 'private',
        ]);

        // Assert — スコープ外の講座は対象外のため403ではなく200
        $response->assertStatus(200);
        $response->assertJson([
            'result' => 'true',
            'updated_ids' => [],
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $ownerCourse->id,
            'status' => CourseStatusEnum::PUBLIC->value,
        ]);
    }

    public function test_ステータスが未送信_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        Course::factory()->create([
            'instructor_id' => $manager->id,
            'status' => CourseStatusEnum::DRAFT->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.course.put-status'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_ステータスが文字列でない_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.course.put-status'), [
            'status' => 123,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_ステータスがdraft_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.course.put-status'), [
            'status' => 'draft',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
