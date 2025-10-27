<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_受講期限なし_講座登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');
        // act
        $response = $this->post('/api/v1/instructor/course', [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => 2,
            'deadline_type' => 'none',
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'title' => 'テスト講座',
        ]);
        $this->assertDatabaseHas('course_tag', [
            'course_id' => 8,
            'tag_id' => 2,
        ]);

        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => 8,
        ]);
    }

    public function test_固定受講期限あり_講座登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // act
        $response = $this->post('/api/v1/instructor/course', [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => 2,
            'deadline_type' => 'fixed_date',
            'fixed_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'title' => 'テスト講座',
        ]);

        $this->assertDatabaseHas('course_tag', [
            'course_id' => 8,
            'tag_id' => 2,
        ]);

        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => 8,
            'fixed_date' => now()->addDays(30)->format('Y-m-d 00:00:00'),
        ]);
    }

    public function test_相対受講期限あり_講座登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // act
        $response = $this->post('/api/v1/instructor/course', [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => 2,
            'deadline_type' => 'relative_days',
            'relative_days' => 30,
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'title' => 'テスト講座',
        ]);

        $this->assertDatabaseHas('course_tag', [
            'course_id' => 8,
            'tag_id' => 2,
        ]);

        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => 8,
            'relative_days' => 30,
        ]);
    }

    public function test_無効なタグの指定_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');
        // act
        $response = $this->post('/api/v1/instructor/course', [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => 1,
            'deadline_type' => 'none',
        ]);

        // assert
        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Not Found Tag.',
        ]);
    }

    public function test_バリデーションエラー_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');
        // act
        $response = $this->post('/api/v1/instructor/course', [
            'title' => '',
            'image' => null,
            'tag_id' => '',
            'deadline_type' => '',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title' => 'The title field is required.',
            'image' => 'The image field is required.',
            'tag_id' => 'The tag id field is required.',
            'deadline_type' => 'The deadline type field is required.',
        ]);
    }
}
