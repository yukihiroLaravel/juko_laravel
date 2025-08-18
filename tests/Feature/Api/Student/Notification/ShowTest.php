<?php

namespace Tests\Feature\Api\Student\Notification;

use App\Model\Course;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせ詳細取得_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student, 'web');

        // act
        $response = $this->getJson('/api/v1/notification/1');

        // assert
        $response->assertStatus(200);
    }

    public function test_講座の期限切れ_失敗(): void
    {
        // arrange
        // 期限切れの講座を設定
        Course::find(1)->update(['attendance_deadline' => now()->subDays(1)]);
        $student = Student::find(1);
        $this->actingAs($student, 'web');

        // act
        $response = $this->getJson('/api/v1/notification/1');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'The course has expired.',
        ]);
    }
}
