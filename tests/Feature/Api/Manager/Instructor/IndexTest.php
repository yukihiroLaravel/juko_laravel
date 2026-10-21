<?php

namespace Tests\Feature\Api\Manager\Instructor;

use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_配下の講師の一覧を取得できる(): void
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
        $instructorIds = $response->json('data.instructors.*.instructor_id');
        $this->assertEqualsCanonicalizing([$subordinate1->id, $subordinate2->id], $instructorIds);
    }

    public function test_マネージャーでない講師は配下の講師の一覧を取得できない(): void
    {
        // Arrange
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
