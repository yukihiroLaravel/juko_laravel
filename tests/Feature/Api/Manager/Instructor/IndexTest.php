<?php

namespace Tests\Feature\Api\Manager\Instructor;

use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_講師講座一覧取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate1 = Instructor::factory()->create(['type' => 'instructor']);
        $subordinate2 = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate1->id,
        ]);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate2->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.instructors');
    }

    public function test_マネージャーではない_失敗(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.index'));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
