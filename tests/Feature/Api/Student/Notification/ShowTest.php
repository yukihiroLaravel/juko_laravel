<?php

namespace Tests\Feature\Api\Student\Notification;

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
}
