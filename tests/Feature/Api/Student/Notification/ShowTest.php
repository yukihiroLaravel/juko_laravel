<?php

namespace Tests\Feature\Api\Student\Notification;

use App\Model\Attendance;
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

    public function test_お知らせ取得できる(): void
    {
        // arrange
        $this->loginAsStudent();

        // act
        $response = $this->getJson('/api/v1/notification/1');

        // assert
        $response->assertStatus(200);
    }

    public function test_講座の期限切れ_失敗(): void
    {
        // arrange
        $this->loginAsStudent();
        // 期限切れの講座にする
        Attendance::find(1)->update(['attendance_deadline' => now()->subDays(1)]);

        // act
        $response = $this->getJson('/api/v1/notification/1');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'The course has expired.',
        ]);
    }
}
