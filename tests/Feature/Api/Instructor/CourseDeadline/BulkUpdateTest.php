<?php

namespace Tests\Feature\Api\Instructor\CourseDeadline;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_自分の講座の受講期限を固定日に変更_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.deadline.bulk-update'), [
            'courses' => [$course->id],
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE->value,
            'fixed_date' => '2026-12-31',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course->id,
            'fixed_date' => '2026-12-31 00:00:00',
            'relative_days' => null,
        ]);
    }

    public function test_自分の講座の受講期限を相対日数に変更_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.deadline.bulk-update'), [
            'courses' => [$course->id],
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
            'relative_days' => 30,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course->id,
            'fixed_date' => null,
            'relative_days' => 30,
        ]);
    }

    public function test_自分の講座の受講期限を削除_成功(): void
    {
        // Arrange — 既存のCourseDeadlineレコードを作成
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        CourseDeadline::factory()->create([
            'course_id' => $course->id,
            'fixed_date' => '2026-12-31',
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.deadline.bulk-update'), [
            'courses' => [$course->id],
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => $course->id,
        ]);
    }

    public function test_複数講座の受講期限を一括変更_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course1 = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course2 = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.deadline.bulk-update'), [
            'courses' => [$course1->id, $course2->id],
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE->value,
            'fixed_date' => '2026-06-30',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 2,
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course1->id,
            'fixed_date' => '2026-06-30 00:00:00',
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course2->id,
            'fixed_date' => '2026-06-30 00:00:00',
        ]);
    }

    public function test_マネージャーが配下の講師の講座を更新_成功(): void
    {
        // Arrange — マネージャーが配下の講師の講座を更新
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.deadline.bulk-update'), [
            'courses' => [$course->id],
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
            'relative_days' => 60,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course->id,
            'relative_days' => 60,
        ]);
    }

    public function test_他の講師の講座を更新_失敗(): void
    {
        // Arrange — 配下でない講師の講座を更新しようとする
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $otherInstructor = Instructor::factory()->create();
        $otherCourse = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.deadline.bulk-update'), [
            'courses' => [$otherCourse->id],
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE->value,
            'fixed_date' => '2026-12-31',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_配下でない講師の講座を含めて更新_失敗(): void
    {
        // Arrange — 自分の講座と配下でない講師の講座を混ぜて更新
        $manager = Instructor::factory()->create();
        $ownCourse = Course::factory()->create(['instructor_id' => $manager->id]);
        $otherInstructor = Instructor::factory()->create();
        $otherCourse = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.deadline.bulk-update'), [
            'courses' => [$ownCourse->id, $otherCourse->id],
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE->value,
            'fixed_date' => '2026-12-31',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_空のcoursesで更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.deadline.bulk-update'), [
            'courses' => [],
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['courses']);
    }

    public function test_未認証でアクセス_失敗(): void
    {
        // Act
        $response = $this->patchJson(route('instructor.courses.deadline.bulk-update'), [
            'courses' => [1],
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE->value,
            'fixed_date' => '2026-12-31',
        ]);

        // Assert
        $response->assertStatus(401);
    }
}
