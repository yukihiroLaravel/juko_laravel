<?php

namespace Tests\Feature\Service\Student;

use App\Exceptions\ExpiredAuthorizationCodeException;
use App\Exceptions\TryCountOverAuthorizationCodeException;
use App\Model\TemporaryStudent;
use App\Services\Student\VerifyCodeService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証コードが有効(): void
    {
        // Arrange
        $service = new VerifyCodeService;
        $temporaryStudent = TemporaryStudent::factory()->create([
            'trial_count' => 0,
            'code' => '1234',
            'expire_at' => now()->addMinutes(10),
        ]);

        // Act
        $result = $service($temporaryStudent, CarbonImmutable::now(), '1234');

        // Assert
        $this->assertTrue($result);
    }

    public function test_認証コードが無効_期限切れ(): void
    {
        // Arrange
        $this->expectException(ExpiredAuthorizationCodeException::class);

        $service = new VerifyCodeService;
        $temporaryStudent = TemporaryStudent::factory()->create([
            'trial_count' => 0,
            'code' => '1234',
            'expire_at' => now()->subMinutes(10),
        ]);

        // Act
        $service($temporaryStudent, CarbonImmutable::now(), '1234');
    }

    public function test_認証コードが無効_試行回数超過(): void
    {
        // Arrange
        $this->expectException(TryCountOverAuthorizationCodeException::class);

        $service = new VerifyCodeService;
        $temporaryStudent = TemporaryStudent::factory()->create([
            'trial_count' => 3,
            'code' => '1234',
            'expire_at' => now()->addMinutes(10),
        ]);

        // Act
        $service($temporaryStudent, CarbonImmutable::now(), '0000');
    }

    public function test_認証コードが無効_試行回数増加(): void
    {
        // Arrange
        $service = new VerifyCodeService;
        $temporaryStudent = TemporaryStudent::factory()->create([
            'trial_count' => 0,
            'code' => '1234',
            'expire_at' => now()->addMinutes(10),
        ]);

        // Act
        $result = $service($temporaryStudent, CarbonImmutable::now(), '0000');

        // Assert
        $this->assertFalse($result);
        $this->assertEquals(1, $temporaryStudent->trial_count);
    }
}
