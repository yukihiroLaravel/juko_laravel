<?php

namespace Tests\Feature\Api\Instructor\Course;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkDeleteTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_マネージャーで自分の講座を一括削除する(): void
    {
        // arrange
        $this->loginAsManager();

        // act
        $response = $this->deleteJson('/api/v1/instructor/course', [
            'courses' => [5],
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'deleted_count' => 1,
        ]);
        $this->assertSoftDeleted('courses', [
            'id' => 5,
        ]);
    }

    public function test_マネージャーで配下の講師の講座を一括削除する(): void
    {
        // arrange
        $this->loginAsManager();

        // act
        $response = $this->deleteJson('/api/v1/instructor/course', [
            'courses' => [3],
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'deleted_count' => 1,
        ]);
        $this->assertSoftDeleted('courses', [
            'id' => 3,
        ]);
    }

    public function test_マネージャーで複数講座を一括削除する(): void
    {
        // arrange
        $this->loginAsManager();

        // act
        $response = $this->deleteJson('/api/v1/instructor/course', [
            'courses' => [5, 3, 7],
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'deleted_count' => 3,
        ]);
        $this->assertSoftDeleted('courses', ['id' => 5]);
        $this->assertSoftDeleted('courses', ['id' => 3]);
        $this->assertSoftDeleted('courses', ['id' => 7]);
    }

    public function test_講師で自分の講座を一括削除する(): void
    {
        // arrange
        $this->loginAsInstructor(3);

        // act
        $response = $this->deleteJson('/api/v1/instructor/course', [
            'courses' => [3, 7],
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'deleted_count' => 2,
        ]);
        $this->assertSoftDeleted('courses', ['id' => 3]);
        $this->assertSoftDeleted('courses', ['id' => 7]);
    }

    public function test_権限がないマネージャーの場合削除できない(): void
    {
        // arrange
        $this->loginAsManager();

        // act
        $response = $this->deleteJson('/api/v1/instructor/course', [
            'courses' => [4],
        ]);

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
        $response = $this->deleteJson('/api/v1/instructor/course', [
            'courses' => [1],
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_一部に権限がない講座を含む場合削除できない(): void
    {
        // arrange
        $this->loginAsManager();

        // act
        $response = $this->deleteJson('/api/v1/instructor/course', [
            'courses' => [5, 4],
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => 5,
            'deleted_at' => null,
        ]);
    }

    public function test_受講生がいる講座は削除できない(): void
    {
        // arrange
        $this->loginAsInstructor(1);

        // act
        $response = $this->deleteJson('/api/v1/instructor/course', [
            'courses' => [1],
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This course has already been taken by students.',
        ]);
    }

    public function test_一部に受講生がいる講座を含む場合削除できない(): void
    {
        // arrange
        $this->loginAsManager();

        // act
        $response = $this->deleteJson('/api/v1/instructor/course', [
            'courses' => [5, 1],
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This course has already been taken by students.',
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => 5,
            'deleted_at' => null,
        ]);
    }
}
