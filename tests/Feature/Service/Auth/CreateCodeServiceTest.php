<?php

namespace Tests\Feature\Service\Auth;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Model\TemporaryInstructor;
use App\Model\TemporaryStudent;
use App\Services\Auth\CreateCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講生の認証コードを生成する(): void
    {
        // Arrange
        $service = new CreateCodeService;

        // Act
        $code = $service(
            existsChecker: fn (string $code) => TemporaryStudent::where('code', $code)->exists(),
        );

        // Assert
        $this->assertNotEmpty($code);
        $this->assertEquals(4, strlen($code));
    }

    public function test_講師の認証コードを生成する(): void
    {
        // Arrange
        $service = new CreateCodeService;

        // Act
        $code = $service(
            existsChecker: fn (string $code) => TemporaryInstructor::where('code', $code)->exists(),
        );

        // Assert
        $this->assertNotEmpty($code);
        $this->assertEquals(4, strlen($code));
    }

    public function test_同じ認証コードが続けて作られたときは発行に失敗する(): void
    {
        // Arrange
        $service = new CreateCodeService;
        TemporaryInstructor::create([
            'trial_count' => 0,
            'code' => '1234',
            'token' => 'abcdefghij',
            'expire_at' => now()->addMinutes(10),
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test1@example.com',
            'type' => 'instructor',
        ]);

        // Assert
        $this->expectException(DuplicateAuthorizationCodeException::class);

        // Act
        $service(
            existsChecker: fn (string $code) => TemporaryInstructor::where('code', $code)->exists(),
            randomGenerator: fn () => '1234'
        );
    }
}
