<?php

namespace Tests\Feature\Api\Instructor\CourseDeadline;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkUpdateTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_自分の講座の受講期限を固定日に変更_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->patchJson('/api/v1/instructor/course/deadline', [
            'courses' => [1],
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE->value,
            'fixed_date' => '2026-12-31',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => 1,
            'fixed_date' => '2026-12-31 00:00:00',
            'relative_days' => null,
        ]);
    }

    public function test_自分の講座の受講期限を相対日数に変更_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->patchJson('/api/v1/instructor/course/deadline', [
            'courses' => [1],
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
            'relative_days' => 30,
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => 1,
            'fixed_date' => null,
            'relative_days' => 30,
        ]);
    }

    public function test_自分の講座の受講期限を削除_成功(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // course_id=2 には既に CourseDeadline レコードが存在する
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => 2,
        ]);

        // act
        $response = $this->patchJson('/api/v1/instructor/course/deadline', [
            'courses' => [2],
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => 2,
        ]);
    }

    public function test_複数講座の受講期限を一括変更_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act (course 1 と 5 は instructor_id=1)
        $response = $this->patchJson('/api/v1/instructor/course/deadline', [
            'courses' => [1, 5],
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE->value,
            'fixed_date' => '2026-06-30',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 2,
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => 1,
            'fixed_date' => '2026-06-30 00:00:00',
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => 5,
            'fixed_date' => '2026-06-30 00:00:00',
        ]);
    }

    public function test_マネージャーが配下の講師の講座を更新_成功(): void
    {
        // arrange
        // instructor_id=1 は manager で、instructor_id=2 を管理している
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act (course 2 は instructor_id=2 の講座)
        $response = $this->patchJson('/api/v1/instructor/course/deadline', [
            'courses' => [2],
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
            'relative_days' => 60,
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => 2,
            'relative_days' => 60,
        ]);
    }

    public function test_他の講師の講座を更新_失敗(): void
    {
        // arrange
        // instructor_id=2 は instructor_id=4 の講座を更新できない
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act (course 4 は instructor_id=4 の講座)
        $response = $this->patchJson('/api/v1/instructor/course/deadline', [
            'courses' => [4],
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE->value,
            'fixed_date' => '2026-12-31',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_配下でない講師の講座を含めて更新_失敗(): void
    {
        // arrange
        // instructor_id=1 は instructor_id=4 を管理していない
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act (course 1 は自分の講座、course 4 は instructor_id=4 の講座)
        $response = $this->patchJson('/api/v1/instructor/course/deadline', [
            'courses' => [1, 4],
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE->value,
            'fixed_date' => '2026-12-31',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_空のcoursesで更新_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->patchJson('/api/v1/instructor/course/deadline', [
            'courses' => [],
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);

        // assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['courses']);
    }

    public function test_未認証でアクセス_失敗(): void
    {
        // act
        $response = $this->patchJson('/api/v1/instructor/course/deadline', [
            'courses' => [1],
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE->value,
            'fixed_date' => '2026-12-31',
        ]);

        // assert
        $response->assertStatus(401);
    }
}
