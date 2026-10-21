<?php

namespace Tests\Feature\Api\Manager\Instructor;

use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Override;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /** AC-ACCOUNT-030 */
    public function test_配下の講師の情報を更新できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->post(route('manager.instructors.update', ['instructor_id' => $subordinate->id]), [
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test_update@example.com',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('instructors', [
            'id' => $subordinate->id,
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test_update@example.com',
        ]);
    }

    /** AC-ACCOUNT-030 */
    public function test_配下でない講師の情報は更新できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->post(route('manager.instructors.update', ['instructor_id' => $otherInstructor->id]), [
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test_update@example.com',
        ], ['Accept' => 'application/json']);

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseMissing('instructors', [
            'id' => $otherInstructor->id,
            'email' => 'test_update@example.com',
        ]);
    }

    /** AC-ACCOUNT-029 */
    public function test_プロフィール画像を差し替えると差し替え前の画像が削除される(): void
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
        Storage::disk('public')->put('instructor/default.jpg', 'old');
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->post(route('manager.instructors.update', ['instructor_id' => $subordinate->id]), [
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test_update@example.com',
            'profile_image' => UploadedFile::fake()->image('test.jpg'),
        ]);

        // Assert
        $response->assertStatus(200);
        Storage::disk('public')->assertMissing('instructor/default.jpg');
        $newImage = $subordinate->fresh()->profile_image;
        $this->assertNotSame('instructor/default.jpg', $newImage);
        Storage::disk('public')->assertExists($newImage);
    }

    public function test_必須項目が空のときは入力エラーになる(): void
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
