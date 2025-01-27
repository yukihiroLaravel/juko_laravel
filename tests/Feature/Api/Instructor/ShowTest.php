<?php

namespace Tests\Feature\Api\Instructor;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_講師取得_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // act
        $response = $this->getJson('/api/v1/instructor');

        // assert
        $response->assertStatus(200);
    }
}
