<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Enums\Course\StatusEnum;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutStatusTest extends TestCase
{
    use RefreshDatabase;

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
        $response = $this->putJson(route('instructor.course.put-status'), [
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
        $response = $this->putJson(route('instructor.course.put-status'), [
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
        $response = $this->putJson(route('instructor.course.put-status'), [
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

    public function test_権限がないマネージャーの場合更新できない(): void
    {
        // Arrange — 配下にない講師の講座
        $manager = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
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

    public function test_権限がない講師の場合更新できない(): void
    {
        // Arrange — 別の講師の講座
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => [$course->id],
            'status' => StatusEnum::PRIVATE->value,
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_一部に権限がない講座を含む場合更新できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $ownCourse = Course::factory()->create([
            'instructor_id' => $manager->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $otherInstructor = Instructor::factory()->create();
        $otherCourse = Course::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
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

    public function test_バリデーションエラー_coursesが未送信(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_バリデーションエラー_coursesが空配列(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => [],
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_バリデーションエラー_coursesが配列でない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => 'abc',
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_バリデーションエラー_courses要素が整数でない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => ['abc'],
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_バリデーションエラー_存在しない_i_dを含む(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => [99999],
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_バリデーションエラー_削除済みの_i_dを含む(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $course->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => [$course->id],
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_バリデーションエラー_statusが未送信(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_バリデーションエラー_statusが文字列でない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => [$course->id],
            'status' => 123,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_バリデーションエラー_statusが下書き(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->putJson(route('instructor.course.put-status'), [
            'courses' => [$course->id],
            'status' => StatusEnum::DRAFT->value,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }
}
