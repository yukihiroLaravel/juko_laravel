<?php

namespace Tests\Feature\Auth;

use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログアウト_成功(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->postJson(route('logout'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Unauthenticated.',
        ]);
        $this->assertGuest();
    }

    public function test_未認証時_ログアウト(): void
    {
        // Act
        $response = $this->postJson(route('logout'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Already Unauthenticated.',
        ]);
    }
}
