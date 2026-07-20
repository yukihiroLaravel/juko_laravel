<?php

namespace Tests\Feature\Service\Auth;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Model\TemporaryInstructor;
use App\Model\TemporaryStudent;
use App\Services\Auth\CredentialGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_生徒の認証コードを生成する(): void
    {
        $service = new CredentialGeneratorService;
        $code = $service->createCode(
            existsChecker: fn (string $code) => TemporaryStudent::where('code', $code)->exists(),
        );

        $this->assertNotEmpty($code);
        $this->assertEquals(4, strlen($code));
    }

    public function test_講師の認証コードを生成する(): void
    {
        $service = new CredentialGeneratorService;
        $code = $service->createCode(
            existsChecker: fn (string $code) => TemporaryInstructor::where('code', $code)->exists(),
        );

        $this->assertNotEmpty($code);
        $this->assertEquals(4, strlen($code));
    }

    public function test_認証コード生成時に重複エラー(): void
    {
        $this->expectException(DuplicateAuthorizationCodeException::class);
        $service = new CredentialGeneratorService;
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

        $service->createCode(
            existsChecker: fn (string $code) => TemporaryInstructor::where('code', $code)->exists(),
            randomGenerator: fn () => '1234'
        );
    }

    public function test_生徒のトークンを生成する(): void
    {
        $service = new CredentialGeneratorService;
        $token = $service->createToken(fn ($token) => TemporaryStudent::where('token', $token)->exists());
        $this->assertNotEmpty($token);
        $this->assertEquals(10, strlen($token));
    }

    public function test_講師のトークンを生成する(): void
    {
        $service = new CredentialGeneratorService;
        $token = $service->createToken(fn ($token) => TemporaryInstructor::where('token', $token)->exists());
        $this->assertNotEmpty($token);
        $this->assertEquals(10, strlen($token));
    }

    public function test_トークン生成時に重複エラー(): void
    {
        $this->expectException(DuplicateAuthorizationTokenException::class);

        $service = new CredentialGeneratorService;
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
        $service->createToken(
            existsChecker: fn (string $token) => TemporaryInstructor::where('token', $token)->exists(),
            randomGenerator: fn () => 'abcdefghij'
        );
    }
}
