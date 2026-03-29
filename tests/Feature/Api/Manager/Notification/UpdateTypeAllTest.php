<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTypeAllTest extends TestCase
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
        $notificationType = 'once';
        $response = $this->putJson(route('manager.notifications.update-type-all'), [
            'notification_type' => $notificationType,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'type' => 'once',
        ]);
    }

    public function test_バリデーションエラー_失敗(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $notificationType = 'action';
        $response = $this->putJson(route('manager.notifications.update-type-all'), [
            'notification_type' => $notificationType,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected notification type is invalid.',
        ]);
    }

    public function test_お知らせ登録_権限エラー(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $notificationType = 'once';
        $response = $this->putJson(route('manager.notifications.update-type-all'), [
            'notification_type' => $notificationType,
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
