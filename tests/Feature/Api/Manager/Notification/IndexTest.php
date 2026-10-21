<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    /** AC-NOTIF-028 */
    public function test_自分と配下の講師のお知らせを一覧で取得できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        Notification::factory()->create(['instructor_id' => $manager->id]);
        Notification::factory()->count(2)->create(['instructor_id' => $subordinate->id]);
        Notification::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data.notifications');
    }

    public function test_マネージャーでない講師はお知らせの一覧を取得できない(): void
    {
        // Arrange
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
