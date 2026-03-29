<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_お知らせ一覧取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course1 = Course::factory()->create(['instructor_id' => $manager->id]);
        $course2 = Course::factory()->create(['instructor_id' => $subordinate->id]);
        Notification::factory()->create([
            'course_id' => $course1->id,
            'instructor_id' => $manager->id,
        ]);
        Notification::factory()->count(2)->create([
            'course_id' => $course2->id,
            'instructor_id' => $subordinate->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data.notifications');
    }

    public function test_権限エラー_失敗(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.notifications.index'));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
