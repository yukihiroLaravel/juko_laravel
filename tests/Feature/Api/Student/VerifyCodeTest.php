<?php

namespace Tests\Feature\Api\Student;

use App\Model\TemporaryStudent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_トークン認証_成功(): void
    {
        // Arrange
        $temporaryStudent = TemporaryStudent::factory()->create([
            'code' => '1234',
            'expire_at' => now()->addMinutes(10),
        ]);

        // Act
        $response = $this->postJson(
            route('student.verify-code', ['token' => $temporaryStudent->token]),
            [
                'code' => '1234',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        );

        // Assert
        $response->assertStatus(200);
    }

    public function test_トークン認証_失敗_期限切れ(): void
    {
        // Arrange
        $temporaryStudent = TemporaryStudent::factory()->create([
            'code' => '1234',
            'expire_at' => now()->subMinutes(10),
        ]);

        // Act
        $response = $this->postJson(
            route('student.verify-code', ['token' => $temporaryStudent->token]),
            [
                'code' => '1234',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        );

        // Assert
        $response->assertStatus(400);
    }

    public function test_トークン認証_失敗_認証コード不一致(): void
    {
        // Arrange
        $temporaryStudent = TemporaryStudent::factory()->create([
            'code' => '1234',
            'expire_at' => now()->subMinutes(10),
        ]);

        // Act
        $response = $this->postJson(
            route('student.verify-code', ['token' => $temporaryStudent->token]),
            [
                'code' => '0000',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        );

        // Assert
        $response->assertStatus(400);
    }

    public function test_トークン認証_失敗_試行回数超過(): void
    {
        // Arrange
        $temporaryStudent = TemporaryStudent::factory()->create([
            'trial_count' => 3,
            'code' => '1234',
            'expire_at' => now()->subMinutes(10),
        ]);

        // Act
        $response = $this->postJson(
            route('student.verify-code', ['token' => $temporaryStudent->token]),
            [
                'code' => '0000',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        );

        // Assert
        $response->assertStatus(400);
        $this->assertEmpty(TemporaryStudent::all());
    }
}
