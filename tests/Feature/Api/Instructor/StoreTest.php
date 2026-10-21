<?php

namespace Tests\Feature\Api\Instructor;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Services\Auth\CreateCodeService;
use App\Services\Auth\CreateTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    /** AC-ACCOUNT-001 */
    public function test_講師を仮登録として受け付ける(): void
    {
        // Act
        $response = $this->postJson(route('instructor.register'), [
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'testtest@exmaple.com',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('temporary_instructors', [
            'email' => 'testtest@exmaple.com',
        ]);
        $this->assertDatabaseMissing('instructors', [
            'email' => 'testtest@exmaple.com',
        ]);
    }

    public function test_入力がすべて空のときは仮登録できない(): void
    {
        // Act
        $response = $this->postJson(route('instructor.register'), [
            'nick_name' => '',
            'last_name' => '',
            'first_name' => '',
            'email' => '',
        ]);

        // Assert
        $response->assertStatus(422);
    }

    /**
     * @return array<string, array{class-string, \Throwable}>
     */
    public static function duplicateFailures(): array
    {
        return [
            '認証コードが重複する' => [
                CreateCodeService::class,
                new DuplicateAuthorizationCodeException('Failed to generate unique authorization code.'),
            ],
            '照会用の符号が重複する' => [
                CreateTokenService::class,
                new DuplicateAuthorizationTokenException('Failed to generate unique authorization token.'),
            ],
        ];
    }

    /** AC-ACCOUNT-006 */
    #[DataProvider('duplicateFailures')]
    public function test_重複しない値を生成できないときは仮登録できない(string $service, \Throwable $exception): void
    {
        // Arrange
        $this->mock($service, function ($mock) use ($exception) {
            $mock->shouldReceive('__invoke')->andThrow($exception);
        });

        // Act
        $response = $this->postJson(route('instructor.register'), [
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'testtest@exmaple.com',
        ]);

        // Assert
        $response->assertStatus(400);
        $this->assertDatabaseMissing('temporary_instructors', [
            'email' => 'testtest@exmaple.com',
        ]);
    }
}
