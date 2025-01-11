<?php

namespace Tests\Feature\Service\Instructor;

use App\Exceptions\ExpiredAuthorizationCodeException;
use App\Exceptions\TryCountOverAuthorizationCodeException;
use App\Model\TemporaryInstructor;
use App\Services\Instructor\VerifyCodeService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_認証コードが有効(): void
    {
        $service = new VerifyCodeService;
        $temporaryInstructor = TemporaryInstructor::create([
            'trial_count' => 0,
            'code' => '1234',
            'token' => 'abcdefghij',
            'expire_at' => now()->addMinutes(10),
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'testtest@example.com',
            'type' => 'instructor',
        ]);

        $result = $service($temporaryInstructor, CarbonImmutable::now(), '1234');
        $this->assertTrue($result);
    }

    public function test_認証コードが無効_期限切れ(): void
    {
        $this->expectException(ExpiredAuthorizationCodeException::class);

        $service = new VerifyCodeService;
        $temporaryInstructor = TemporaryInstructor::create([
            'trial_count' => 0,
            'code' => '1234',
            'token' => 'abcdefghij',
            'expire_at' => now()->subMinutes(10),
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'testtest@example.com',
            'type' => 'instructor',
        ]);

        $service($temporaryInstructor, CarbonImmutable::now(), '1234');
    }

    public function test_認証コードが無効_試行回数超過(): void
    {
        $this->expectException(TryCountOverAuthorizationCodeException::class);

        $service = new VerifyCodeService;
        $temporaryInstructor = TemporaryInstructor::create([
            'trial_count' => 3,
            'code' => '1234',
            'token' => 'abcdefghij',
            'expire_at' => now()->addMinutes(10),
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test@example.com',
            'type' => 'instructor',
        ]);

        $service($temporaryInstructor, CarbonImmutable::now(), '0000');
    }

    public function test_認証コードが無効_試行回数増加(): void
    {
        $service = new VerifyCodeService;
        $temporaryInstructor = TemporaryInstructor::create([
            'trial_count' => 0,
            'code' => '1234',
            'token' => 'abcdefghij',
            'expire_at' => now()->addMinutes(10),
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'test@example.com',
            'type' => 'instructor',
        ]);

        $result = $service($temporaryInstructor, CarbonImmutable::now(), '0000');

        $this->assertFalse($result);
        $this->assertEquals(1, $temporaryInstructor->trial_count);
    }
}
