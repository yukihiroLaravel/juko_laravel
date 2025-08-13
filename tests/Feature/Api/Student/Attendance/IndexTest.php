<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_受講一覧を取得_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->getJson('/api/v1/attendance/index');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_受講一覧を取得_タグ指定_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->getJson('/api/v1/attendance/index?tag_id=1');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_タグ名で検索_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->getJson('/api/v1/attendance/index?search_word=バックエンド');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_講座名で検索_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->getJson('/api/v1/attendance/index?search_word=Vue');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }
}
