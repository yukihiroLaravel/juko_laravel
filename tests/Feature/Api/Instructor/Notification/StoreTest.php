<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_お知らせ登録_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.course.notification.store', ['course_id' => $course->id]), [
            'title' => 'title',
            'type' => 'always',
            'start_date' => '2022-01-01 00:00:00',
            'end_date' => '2022-01-02 00:00:00',
            'content' => 'content',
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'course_id' => $course->id,
            'title' => 'title',
            'type' => TypeEnum::ALWAYS,
            'start_date' => '2022-01-01 00:00:00',
            'end_date' => '2022-01-02 00:00:00',
            'content' => 'content',
            'status' => 'private',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // Arrange — 他の講師の講座にお知らせ登録
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.course.notification.store', ['course_id' => $course->id]), [
            'title' => 'title',
            'type' => 'always',
            'start_date' => '2022-01-01 00:00:00',
            'end_date' => '2022-01-02 00:00:00',
            'content' => 'content',
            'status' => 'private',
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
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.course.notification.store', ['course_id' => 'aaa']), [
            'title' => '',
            'type' => '',
            'start_date' => '',
            'end_date' => '',
            'content' => '',
            'status' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'title',
            'type',
            'start_date',
            'end_date',
            'content',
            'status',
        ]);
    }
}
