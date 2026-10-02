<?php

namespace Tests\Feature\Service\Auth;

use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Model\TemporaryInstructor;
use App\Model\TemporaryStudent;
use App\Services\Auth\CreateTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講生のトークンを生成する(): void
    {
        // Arrange
        $service = new CreateTokenService;

        // Act
        $token = $service(fn ($token) => TemporaryStudent::where('token', $token)->exists());

        // Assert
        $this->assertNotEmpty($token);
        $this->assertEquals(10, strlen($token));
    }

    public function test_講師のトークンを生成する(): void
    {
        // Arrange
        $service = new CreateTokenService;

        // Act
        $token = $service(fn ($token) => TemporaryInstructor::where('token', $token)->exists());

        // Assert
        $this->assertNotEmpty($token);
        $this->assertEquals(10, strlen($token));
    }

    public function test_同じトークンが続けて作られたときは発行に失敗する(): void
    {
        // Arrange
        $service = new CreateTokenService;
        TemporaryInstructor::create([
            'trial_count' => 0,
            'code' => '1234',
            'token' => 'abcdefghij',
            'expire_at' => now()->addMinutes(10),
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test2@example.com',
            'type' => 'instructor',
        ]);

        // Assert
        $this->expectException(DuplicateAuthorizationTokenException::class);

        // Act
        $service(
            existsChecker: fn (string $token) => TemporaryInstructor::where('token', $token)->exists(),
            randomGenerator: fn () => 'abcdefghij'
        );
    }
}
