<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせ登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        $course = Course::find(1);

        // act
        $response = $this->postJson('/api/v1/manager/course/'.$course->id.'/notification', [
            'title' => 'test',
            'type' => 'once',
            'start_date' => '2025-01-01 10:00:00',
            'end_date' => '2025-01-01 18:00:00',
            'content' => 'これはテストの内容です',
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'title' => 'test',
            'type' => TypeEnum::ONCE,
            'course_id' => $course->id,
        ]);
    }

    public function test_期限切れ講座_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        Course::find(1)->update(['attendance_deadline' => now()->subDays(1)]);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/manager/course/1/notification', [
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

    public function test_お知らせ登録_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        $course = Course::find(1);

        // act
        $response = $this->postJson('/api/v1/manager/course/'.$course->id.'/notification', [
            'title' => '', // 空
            'type' => '',  // 空
            'start_date' => '',  // 空
            'end_date' => '',  // 空
            'content' => '',  // 空
            'status' => '',  // 空
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title',
            'type',
            'start_date',
            'end_date',
            'content',
            'status',
        ]);
    }

    public function test_お知らせ登録_権限エラー(): void
    {
        // arrange
        $unauthorizedInstructor = Instructor::find(2);
        $this->actingAs($unauthorizedInstructor, 'instructor');

        $course = Course::find(1);

        // act
        $response = $this->postJson('/api/v1/manager/course/'.$course->id.'/notification', [
            'title' => '権限なしテスト',
            'type' => 'once',
            'start_date' => '2025-01-01 10:00:00',
            'end_date' => '2025-01-10 18:00:00',
            'content' => 'これはテストの内容です。',
            'status' => 'public',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_権限がない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/manager/course/1/notification', [
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
}
