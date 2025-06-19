<?php

namespace Tests\Feature\Api\Instructor\Notification;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Model\Instructor;

class UpdateTypeAllTest extends TestCase
{
    // データベース初期化
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_空の配列返却_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        $notificationType = 'once';

        $response = $this->putJson("/api/v1/instructor/notification/type/{$notificationType}/all");

        $response->assertStatus(200);
        $response->assertExactJson([]);
    }
}
