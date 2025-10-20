<?php

namespace Tests\Feature\Api\Instructor\CourseDeadline;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearAllTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_講座期限をクリアしている(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/deadline/clear-all');

        // assert
        $response->assertStatus(200)
            ->assertJson([
                'result' => true,
            ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => 2,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => 2,
            'deadline_type' => 'none',
        ]);
    }
}
