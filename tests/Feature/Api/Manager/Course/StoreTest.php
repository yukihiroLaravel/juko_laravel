<?php

namespace Tests\Feature\Api\Manager\Course;

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

    public function test_講座登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // act
        $response = $this->post('/api/v1/manager/course', [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => 1,
            'attendance_deadline' => now()->addDays(30)->format('Y-m-d'),
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'title' => 'テスト講座',
        ]);

        $this->assertDatabaseHas('course_tag', [
            'course_id' => 8,
            'tag_id' => 1,
        ]);
    }

    public function test_無効なタグの指定_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // act
        $response = $this->post('/api/v1/manager/course', [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => 2,
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
        $response = $this->post('/api/v1/manager/course', [
            'title' => '',
            'image' => null,
            'tag_id' => '',
            'attendance_deadline' => 'invalid-date',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title' => 'The title field is required.',
            'image' => 'The image field is required.',
            'tag_id' => 'The tag id field is required.',
            'attendance_deadline' => 'The attendance deadline does not match the format Y-m-d.',
        ]);
    }
}
