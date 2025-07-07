<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PutStatusAllTest extends TestCase
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

    public function test_お知らせステータス一括更新_public_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson("/api/v1/manager/notification/status/all", [
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => 1,
            'status' => 'public',
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => 2,
            'status' => 'public',
        ]);
    }

    public function test_お知らせステータス一括更新_private_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson("/api/v1/manager/notification/status/all", [
            'status' => 'private',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => 1,
            'status' => 'private',
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => 2,
            'status' => 'private',
        ]);
    }

    public function test_ステータス空欄_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson('/api/v1/manager/notification/status/all', [
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
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson("/api/v1/manager/notification/status/all", [
            'status' => 'invalid_status',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected status is invalid.',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->putJson("/api/v1/manager/notification/status/all", [
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_認証なし_失敗(): void
    {
        // act
        $response = $this->putJson("/api/v1/manager/notification/status/all", [
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(401);
    }
}
