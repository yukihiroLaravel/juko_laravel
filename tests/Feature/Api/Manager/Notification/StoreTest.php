<?php

namespace Tests\Feature;

use App\Model\Instructor;
use App\Model\Course;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

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
        $response = $this->postJson('/api/v1/manager/course/' . $course->id . '/notification', [
            'title' => 'test',
            'type' => 'once',
            'start_date' => '2025-01-01 10:00:00',
            'end_date' => '2025-01-01 18:00:00',
            'content' => 'これはテストの内容です',
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'title' => 'test',
            'type' => Notification::TYPE_ONCE_INT,
            'course_id' => $course->id,
        ]);
    }

    public function test_お知らせ登録_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        $course = Course::find(1);

        //act
        $response = $this->postJson('/api/v1/manager/course/' . $course->id . '/notification', [
            'title' => '', // 空
            'type' => '',  // 空
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title',
            'type',
            'start_date',
            'end_date',
            'content',
        ]);
    }

    public function test_お知らせ登録_権限エラー(): void
    {
        // arrange
        $unauthorizedInstructor = Instructor::find(2);
        $this->actingAs($unauthorizedInstructor, 'instructor');
    
        $course = Course::find(1);
        
        // act
        $response = $this->postJson('/api/v1/manager/course/' . $course->id . '/notification', [
            'title' => '権限なしテスト',
            'type' => 'once',
            'start_date' => '2025-01-01 10:00:00',
            'end_date' => '2025-01-10 18:00:00',
            'content' => 'これはテストの内容です。',
        ]);
    
        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
