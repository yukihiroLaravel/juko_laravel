<?php

namespace Tests\Feature\Api\Manager\Course;

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
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/course/1');

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

    public function test_バリデーションエラー_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/course/aaa');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }
}
