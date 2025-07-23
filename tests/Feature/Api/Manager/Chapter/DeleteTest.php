<?php

namespace Tests\Feature\Api\Manager\Chapter;

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
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/manager/course/5/chapter/6');

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);

        // 論理削除されているか確認
        $this->assertSoftDeleted('chapters', [
            'id' => 6,
        ]);
    }

    public function test_配下の講師チャプター削除_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/manager/course/2/chapter/4');

        // assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
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
        $response = $this->deleteJson('/api/v1/manager/course/1/chapter/1');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This lesson has attendance.',
        ]);
    }

    public function test_配下でない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(4);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/manager/course/2/chapter/4');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_マネージャーではない講師_失敗(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/manager/course/2/chapter/4');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->deleteJson('/api/v1/manager/course/aaa/chapter/bbb');

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapter_id',
        ]);
    }
}
