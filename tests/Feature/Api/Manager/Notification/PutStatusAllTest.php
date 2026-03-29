<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutStatusAllTest extends TestCase
{
    use RefreshDatabase;

    public function test_お知らせステータス一括更新_public_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $notification1 = Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $manager->id,
            'status' => 'private',
        ]);
        $notification2 = Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $manager->id,
            'status' => 'private',
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.put-status-all'), [
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification1->id,
            'status' => 'public',
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification2->id,
            'status' => 'public',
        ]);
    }

    public function test_お知らせステータス一括更新_private_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $notification1 = Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $manager->id,
            'status' => 'public',
        ]);
        $notification2 = Notification::factory()->create([
            'course_id' => $course->id,
            'instructor_id' => $manager->id,
            'status' => 'public',
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.put-status-all'), [
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification1->id,
            'status' => 'private',
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification2->id,
            'status' => 'private',
        ]);
    }

    public function test_ステータス空欄_失敗(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.put-status-all'), [
            'status' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'status' => 'The status field is required.',
        ]);
    }

    public function test_無効なステータス_失敗(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.put-status-all'), [
            'status' => 'invalid_status',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected status is invalid.',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.put-status-all'), [
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_認証なし_失敗(): void
    {
        // Act
        $response = $this->putJson(route('manager.notifications.put-status-all'), [
            'status' => 'public',
        ]);

        // Assert
        $response->assertStatus(401);
    }
}
