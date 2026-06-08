<?php

namespace Tests\Feature\Api\Manager\Instructor;

use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_講師更新_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create([
            'type' => 'instructor',
            'profile_image' => 'instructor/default.jpg',
        ]);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $this->actingAs($manager, 'instructor');

        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('manager.instructors.update', ['instructor_id' => $subordinate->id]), [
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test_update@exmaple.com',
            'profile_image' => $file,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('instructors', [
            'id' => $subordinate->id,
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test_update@exmaple.com',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->post(route('manager.instructors.update', ['instructor_id' => $manager->id]), [
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
