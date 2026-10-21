<?php

namespace Tests\Feature\Service\Auth;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Model\TemporaryInstructor;
use App\Model\TemporaryStudent;
use App\Services\Auth\CreateCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AC-ACCOUNT-005
     *
     * @param  class-string<TemporaryStudent|TemporaryInstructor>  $temporaryModel
     */
    #[DataProvider('temporaryModelProvider')]
    public function test_認証コードは4文字で生成する(string $temporaryModel): void
    {
        // Arrange
        $service = new CreateCodeService;

        // Act
        $code = $service(
            existsChecker: fn (string $code) => $temporaryModel::where('code', $code)->exists(),
        );

        // Assert
        $this->assertEquals(4, strlen($code));
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
    public function test_既存の仮登録と重なる認証コードしか作れないときは発行に失敗する(): void
    {
        // Arrange
        $service = new CreateCodeService;
        TemporaryInstructor::factory()->create(['code' => '1234']);

        // Assert
        $this->expectException(DuplicateAuthorizationCodeException::class);

        // Act
        $service(
            existsChecker: fn (string $code) => TemporaryInstructor::where('code', $code)->exists(),
            randomGenerator: fn () => '1234'
        );
    }
}
