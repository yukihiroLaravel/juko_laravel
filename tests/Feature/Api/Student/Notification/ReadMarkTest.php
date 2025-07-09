<?php

namespace tests\Feature\Api\Student\Notification;

use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadMarkTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせ既読登録成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student, 'web');

        // act
        $response = $this->postJson('/api/v1/notification/mark-read', [
            'notification_id' => 4,
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
    }
}
