<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせ登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/2/notification', [
            'title' => 'title',
            'type' => 'always',
            'start_date' => '2022-01-01 00:00:00',
            'end_date' => '2022-01-02 00:00:00',
            'content' => 'content',
            'status' => 'private',
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'course_id' => 2,
            'title' => 'title',
            'type' => TypeEnum::ALWAYS,
            'start_date' => '2022-01-01 00:00:00',
            'end_date' => '2022-01-02 00:00:00',
            'content' => 'content',
            'status' => 'private',
        ]);
    }

    public function test_期限切れ講座_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        Course::find(2)->update(['attendance_deadline' => now()->subDays(1)]);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/2/notification', [
            'title' => 'title',
            'type' => 'always',
            'start_date' => '2022-01-01 00:00:00',
            'end_date' => '2022-01-02 00:00:00',
            'content' => 'content',
            'status' => 'private',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'The course has expired.',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/2/notification', [
            'title' => 'title',
            'type' => 'always',
            'start_date' => '2022-01-01 00:00:00',
            'end_date' => '2022-01-02 00:00:00',
            'content' => 'content',
            'status' => 'private',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/course/aaa/notification', [
            'title' => '',
            'type' => '',
            'start_date' => '',
            'end_date' => '',
            'content' => '',
            'status' => '',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'title',
            'type',
            'start_date',
            'end_date',
            'content',
            'status',
        ]);
    }
}
