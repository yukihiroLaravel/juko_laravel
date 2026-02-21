<?php

namespace Tests\Feature\Api\Manager\Course;

use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講期限なし_講座更新_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('manager.course.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'image' => $file,
            'status' => 'private',
            'deadline_type' => 'none',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'テスト講座',
            'status' => 'private',
        ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => $course->id,
        ]);
    }

    public function test_固定受講期限あり_講座更新_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('manager.course.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'image' => $file,
            'status' => 'private',
            'deadline_type' => 'fixed_date',
            'fixed_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'テスト講座',
            'status' => 'private',
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course->id,
            'fixed_date' => now()->addDays(30)->format('Y-m-d 00:00:00'),
        ]);
    }

    public function test_相対受講期限あり_講座更新_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('manager.course.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'image' => $file,
            'status' => 'private',
            'deadline_type' => 'relative_days',
            'relative_days' => 30,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'title' => 'テスト講座',
            'status' => 'private',
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course->id,
            'relative_days' => 30,
        ]);
    }

    public function test_マネージャー権限がない_失敗(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $nonManager->id]);
        $this->actingAs($nonManager, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('manager.course.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'image' => $file,
            'status' => 'private',
            'deadline_type' => 'none',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_配下の講師でない_失敗(): void
    {
        // Arrange — 別のマネージャーの講座を更新しようとする
        $otherManager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherManager->id]);
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('manager.course.update', ['course_id' => $course->id]), [
            'title' => 'テスト講座',
            'image' => $file,
            'status' => 'private',
            'deadline_type' => 'none',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->post(route('manager.course.update', ['course_id' => 'aaa']), [
            'title' => '',
            'image' => null,
            'status' => 'invalid_status',
            'deadline_type' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'title',
            'image',
            'status',
            'deadline_type',
        ]);
    }
}
