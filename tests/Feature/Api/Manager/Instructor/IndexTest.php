<?php

namespace Tests\Feature\Api\Manager\Instructor;

use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_講師講座一覧取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/instructor/index');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.instructors');
        $response->assertJson([
            'data' => [
                'instructors' => [
                    [
                        'instructor_id' => 3,
                        'nick_name' => 'Suzuki',
                        'email' => 'test_instructor3@example.com',
                        'profile_image' => null,
                        'course_count' => 2,
                        'student_count' => 0,
                    ],
                    [
                        'instructor_id' => 2,
                        'nick_name' => 'Hanako',
                        'email' => 'test_instructor2@example.com',
                        'profile_image' => null,
                        'course_count' => 2,
                        'student_count' => 1,
                    ],
                ],
                'pagination' => [
                    'page' => 1,
                    'total' => 2,
                ],
            ],
        ]);
    }

    public function test_期限切れの講座が存在_講師講座一覧取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        Course::find(6)->update(['attendance_deadline' => now()->subDays(1)]); // 期限切れの講座を設定
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/instructor/index');

        // assert
        $response->dump();
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.instructors');
        $response->assertJson([
            'data' => [
                'instructors' => [
                    [
                        'instructor_id' => 3,
                        'nick_name' => 'Suzuki',
                        'email' => 'test_instructor3@example.com',
                        'profile_image' => null,
                        'course_count' => 2,
                        'student_count' => 0,
                    ],
                    [
                        'instructor_id' => 2,
                        'nick_name' => 'Hanako',
                        'email' => 'test_instructor2@example.com',
                        'profile_image' => null,
                        'course_count' => 2,
                        'student_count' => 1,
                    ],
                ],
                'pagination' => [
                    'page' => 1,
                    'total' => 2,
                ],
            ],
        ]);
    }

    public function test_マネージャーではない_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->getJson('/api/v1/manager/instructor/aaa/course/index');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
