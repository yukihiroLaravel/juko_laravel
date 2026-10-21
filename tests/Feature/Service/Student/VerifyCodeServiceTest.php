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

    public function test_認証コードが一致すれば確認が通る(): void
    {
        // Arrange
        $service = new VerifyCodeService;
        $temporaryStudent = TemporaryStudent::factory()->create([
            'code' => '1234',
        ]);

        // Act
        $result = $service($temporaryStudent, CarbonImmutable::now(), '1234');

        // Assert
        $this->assertTrue($result);
    }

    /** AC-ACCOUNT-012 */
    public function test_有効期限を過ぎていると確認できない(): void
    {
        // Arrange
        $this->expectException(ExpiredAuthorizationCodeException::class);

        $service = new VerifyCodeService;
        $temporaryStudent = TemporaryStudent::factory()->create([
            'expire_at' => now()->subMinutes(10),
        ]);

        // Act
        $service($temporaryStudent, CarbonImmutable::now(), '1234');
    }

    /** AC-ACCOUNT-014 */
    public function test_失敗が3回を超えると確認できない(): void
    {
        // Arrange
        $this->expectException(TryCountOverAuthorizationCodeException::class);

        $service = new VerifyCodeService;
        $temporaryStudent = TemporaryStudent::factory()->create([
            'trial_count' => 3,
            'code' => '1234',
        ]);

        // Act
        $service($temporaryStudent, CarbonImmutable::now(), '0000');
    }

    /** AC-ACCOUNT-013 */
    public function test_コードが違うと失敗回数が1増える(): void
    {
        // Arrange
        $service = new VerifyCodeService;
        $temporaryStudent = TemporaryStudent::factory()->create([
            'code' => '1234',
        ]);

        // Act
        $result = $service($temporaryStudent, CarbonImmutable::now(), '0000');

        // Assert
        $this->assertFalse($result);
        $this->assertDatabaseHas('temporary_students', [
            'id' => $temporaryStudent->id,
            'trial_count' => 1,
        ]);
    }
}
