<?php

namespace Tests\Feature\Service\Auth;

use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Model\TemporaryInstructor;
use App\Model\TemporaryStudent;
use App\Services\Auth\CreateTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AC-ACCOUNT-005
     *
     * @param  class-string<TemporaryStudent|TemporaryInstructor>  $temporaryModel
     */
    #[DataProvider('temporaryModelProvider')]
    public function test_照会用の符号は10文字で生成する(string $temporaryModel): void
    {
        // Arrange
        $service = new CreateTokenService;

        // Act
        $token = $service(fn ($token) => $temporaryModel::where('token', $token)->exists());

        // Assert
        $this->assertEquals(10, strlen($token));
    }

    /** @return array<string, array{class-string}> */
    public static function temporaryModelProvider(): array
    {
        return [
            '受講生' => [TemporaryStudent::class],
            '講師' => [TemporaryInstructor::class],
        ];
    }

    /** AC-ACCOUNT-006 */
    public function test_既存の仮登録と重なる照会用の符号しか作れないときは発行に失敗する(): void
    {
        // Arrange
        $service = new CreateTokenService;
        TemporaryInstructor::factory()->create(['token' => 'abcdefghij']);

        // Assert
        $this->expectException(DuplicateAuthorizationTokenException::class);

        // Act
        $service(
            existsChecker: fn (string $token) => TemporaryInstructor::where('token', $token)->exists(),
            randomGenerator: fn () => 'abcdefghij'
        );
    }
}
