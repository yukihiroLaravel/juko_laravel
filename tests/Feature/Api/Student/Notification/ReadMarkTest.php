<?php

namespace tests\Feature\Api\Student\Notification;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Model\Student;

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
        $response = $this->postJson('/api/v1/notification/markRead', [
            'notification_ids' => [4]
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
    }
}
