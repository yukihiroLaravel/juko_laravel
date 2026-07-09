<?php

namespace Tests\Feature\Auth;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstructorLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログアウト_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.logout'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
        $this->assertGuest('instructor');
    }

    public function test_未認証時_ログアウト(): void
    {
        // Act
        $response = $this->postJson(route('instructor.logout'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Already Unauthenticated.',
        ]);
    }
}
