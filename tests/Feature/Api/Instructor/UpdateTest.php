<?php

namespace Tests\Feature\Api\Instructor;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_講師更新_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['profile_image' => 'instructor/dummy.jpg']);
        $this->actingAs($instructor, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.update'), [
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test_update@exmaple.com',
            'profile_image' => $file,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('instructors', [
            'id' => $instructor->id,
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test_update@exmaple.com',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->post(route('instructor.update'), [
            'nick_name' => '',
            'last_name' => '',
            'first_name' => '',
            'email' => '',
            'profile_image' => '',
        ]);

        // Assert
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
