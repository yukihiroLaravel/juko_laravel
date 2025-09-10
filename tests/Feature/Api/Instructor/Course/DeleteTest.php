<?php

namespace Tests\Feature\Api\Instructor\Course;

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

    public function test_マネージャーで講座削除する(): void
    {
        // arrange
        $this->loginAsManager();

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/5');

        // assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('courses', [
            'id' => 5,
        ]);
    }

    public function test_マネージャーで配下の講師の講座削除する(): void
    {
        // arrange
        $this->loginAsManager();

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/3');

        // assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('courses', [
            'id' => 3,
        ]);
    }

    public function test_講師で講座削除する(): void
    {
        // arrange
        $this->loginAsInstructor(3);

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/3');

        // assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('courses', [
            'id' => 3,
        ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => 3,
        ]);
    }

    public function test_権限がないマネージャーの場合削除できない(): void
    {
        // arrange
        $this->loginAsManager();

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/4');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_権限がない講師の場合削除できない(): void
    {
        // arrange
        $this->loginAsInstructor();

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/1');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_受講生がいる講座は削除できない(): void
    {
        // arrange
        $this->loginAsInstructor(1);

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/1');

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This course has already been taken by students.',
        ]);
    }

    public function test_バリデーションエラー_失敗(): void
    {
        // arrange
        $this->loginAsManager();

        // act
        $response = $this->deleteJson('/api/v1/instructor/course/aaa'); // 存在しないID

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }
}
