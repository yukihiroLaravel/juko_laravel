<?php

namespace Tests\Feature\Api\Instructor\Chapter;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_チャプター削除_成功(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/2/chapter/4');

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);

        // 論理削除されているか確認
        $this->assertSoftDeleted('chapters', [
            'id' => 4,
        ]);
    }

    public function test_マネージャーの受講済みレッスン削除_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/1/chapter/1');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This lesson has attendance.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/aaa/chapter/bbb');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapter_id',
        ]);
    }
}
