<?php

namespace Tests\Feature\Api\Manager\Course;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_講座更新_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // act
        $response = $this->post('/api/v1/manager/course/1', [
            'title' => 'テスト講座',
            'image' => $file,
            'status' => 'private',
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => 1,
            'title' => 'テスト講座',
            'status' => 'private',
        ]);
    }

    public function test_マネージャー権限がない_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // act
        $response = $this->post('/api/v1/manager/course/1', [
            'title' => 'テスト講座',
            'image' => $file,
            'status' => 'private',
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_配下の講師でない_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // act
        $response = $this->post('/api/v1/manager/course/1', [
            'title' => 'テスト講座',
            'image' => $file,
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
        $response = $this->post('/api/v1/manager/course/aaa', [
            'title' => '', // 空のタイトル
            'image' => null, // 画像なし
            'status' => 'invalid_status', // 無効なステータス
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id', 'title', 'image', 'status',
        ]);
    }
}
