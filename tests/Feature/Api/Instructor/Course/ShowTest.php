<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_講座取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/instructor/course/2');

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'course_id',
                'title',
                'image',
                'status',
                'attendance_deadline',
                'chapters' => [
                    '*' => [
                        'chapter_id',
                        'title',
                        'order',
                        'status',
                        'lessons' => [
                            '*' => [
                                'lesson_id',
                                'title',
                                'remarks',
                                'status',
                                'order',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(3);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/instructor/course/2');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }
}
