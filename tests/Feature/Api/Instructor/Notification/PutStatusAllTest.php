<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutStatusAllTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせ一括更新_public_成功(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/notification/status/all', [
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => 2,
            'status' => 'public',
        ]);
    }

    public function test_お知らせ一括更新_private_成功(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/notification/status/all', [
            'status' => 'private',
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'id' => 2,
            'status' => 'private',
        ]);
    }

    public function test_ステータス空欄_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/notification/status/all', [
            'status' => '',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'status' => 'The status field is required.',
        ]);
    }

    public function test_無効なステータス_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/instructor/notification/status/all', [
            'status' => 'invalid_status',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'status' => 'The selected status is invalid.',
        ]);
    }

    public function test_認証なし_失敗(): void
    {
        // act
        $response = $this->putJson('/api/v1/instructor/notification/status/all', [
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(401);
    }
}
