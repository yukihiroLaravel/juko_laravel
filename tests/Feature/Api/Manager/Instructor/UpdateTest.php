<?php

namespace Tests\Feature\Api\Manager\Instructor;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_講師更新_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // act
        $response = $this->post('/api/v1/manager/instructor/1', [
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test_update@exmaple.com',
            'profile_image' => $file,
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('instructors', [
            'id' => 1,
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test_update@exmaple.com',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->post('/api/v1/manager/instructor/1', [
            'nick_name' => '',
            'last_name' => '',
            'first_name' => '',
            'email' => '',
            'profile_image' => '',
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'nick_name',
            'last_name',
            'first_name',
            'email',
            'profile_image',
        ]);
    }
}
