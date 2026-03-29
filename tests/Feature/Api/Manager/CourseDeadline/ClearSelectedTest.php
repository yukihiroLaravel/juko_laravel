<?php

namespace Tests\Feature\Api\Manager\CourseDeadline;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearSelectedTest extends TestCase
{
    use RefreshDatabase;

    public function test_選択した講座の受講期限をクリアできる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
        ]);
        CourseDeadline::factory()->create([
            'course_id' => $course->id,
            'relative_days' => 30,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'result' => true,
            ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => $course->id,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
    }

    public function test_選択していない講座の受講期限はクリアされない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course1 = Course::factory()->create([
            'instructor_id' => $manager->id,
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
        ]);
        CourseDeadline::factory()->create([
            'course_id' => $course1->id,
            'relative_days' => 30,
        ]);
        $course2 = Course::factory()->create([
            'instructor_id' => $manager->id,
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
        ]);
        CourseDeadline::factory()->create([
            'course_id' => $course2->id,
            'relative_days' => 60,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act — course1のみクリア
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => [$course1->id],
        ]);

        // Assert
        $response->assertStatus(200);

        // course2はクリアされていない
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course2->id,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $course2->id,
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
        ]);
    }

    public function test_複数の講座を同時にクリアできる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course1 = Course::factory()->create([
            'instructor_id' => $manager->id,
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
        ]);
        CourseDeadline::factory()->create([
            'course_id' => $course1->id,
            'relative_days' => 30,
        ]);
        $course2 = Course::factory()->create([
            'instructor_id' => $manager->id,
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
        ]);
        CourseDeadline::factory()->create([
            'course_id' => $course2->id,
            'relative_days' => 60,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => [$course1->id, $course2->id],
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'result' => true,
            ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => $course1->id,
        ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => $course2->id,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $course1->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $course2->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
    }

    public function test_他マネージャーの講座は対象外となる(): void
    {
        // Arrange — 別のマネージャーの講座を指定
        $manager = Instructor::factory()->create();
        $otherManager = Instructor::factory()->create();
        $otherCourse = Course::factory()->create([
            'instructor_id' => $otherManager->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => [$otherCourse->id],
        ]);

        // Assert — サービスは冪等性があるため成功を返すが、変更は行われない
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => $otherCourse->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
    }

    public function test_マネージャーでない場合は403を返す(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $nonManager->id]);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_coursesが空の場合はバリデーションエラーを返す(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => [],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_coursesが未指定の場合はバリデーションエラーを返す(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_存在しない講座_i_dの場合はバリデーションエラーを返す(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => [9999],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_削除済み講座_i_dの場合はバリデーションエラーを返す(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $course->delete(); // 論理削除
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_整数以外の値が含まれる場合はバリデーションエラーを返す(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => ['invalid'],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }
}
