<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutTest extends TestCase
{
    use RefreshDatabase;

    public function test_お知らせ更新_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $notification = Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $manager->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notification.put', ['notification_id' => $notification->id]), [
            'title' => 'update',
            'type' => 'once',
            'start_date' => '2025-01-01 10:00:00',
            'end_date' => '2025-01-01 18:00:00',
            'content' => 'updateテスト',
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'title' => 'update',
            'type' => TypeEnum::ONCE,
            'content' => 'updateテスト',
        ]);
    }

    public function test_お知らせ更新_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $notification = Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $manager->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notification.put', ['notification_id' => $notification->id]), [
            'title' => '',
            'type' => '',
            'start_date' => 'invalid-date',
            'end_date' => '2025-01-01 18:00:00',
            'content' => '',
            'status' => 'aaaa',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title',
            'type',
            'start_date',
            'end_date',
            'content',
            'status',
        ]);
    }

    public function test_お知らせ更新_権限エラー(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $notification = Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $manager->id,
        ]);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notification.put', ['notification_id' => $notification->id]), [
            'title' => '権限なしテスト',
            'type' => 'once',
            'start_date' => '2025-01-01 10:00:00',
            'end_date' => '2025-01-10 18:00:00',
            'content' => 'これはテストの内容です。',
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
