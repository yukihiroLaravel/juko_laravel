<?php

namespace Tests\Feature\Api\Student;

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
    public function test_新規登録の要求は本登録せず仮登録として受け付ける(): void
    {
        // Arrange
        $payload = $this->validPayload();

        // Act
        $response = $this->postJson(route('student.store'), $payload);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseCount('temporary_students', 1);
        $this->assertDatabaseHas('temporary_students', ['email' => $payload['email']]);
        $this->assertDatabaseCount('students', 0);
    }

    public function test_バリデーションエラー(): void
    {
        // Act
        $response = $this->postJson(route('student.store'), [
            'nick_name' => '',
            'last_name' => '',
            'first_name' => '',
            'email' => '',
            'occupation' => '',
            'purpose' => '',
            'birth_date' => '',
            'gender' => '',
            'address' => '',
        ]);

        // Assert
        $response->assertStatus(422);
    }

    /**
     * AC-ACCOUNT-006
     *
     * @param  class-string  $serviceClass
     * @param  class-string<\Throwable>  $exceptionClass
     */
    #[DataProvider('duplicateErrorProvider')]
    public function test_重複しない値を生成できないときは400になり仮登録を作らない(string $serviceClass, string $exceptionClass): void
    {
        // Arrange
        $this->mock($serviceClass, function ($mock) use ($exceptionClass) {
            $mock->shouldReceive('__invoke')->andThrow(new $exceptionClass('Failed to generate a unique value.'));
        });

        // Act
        $response = $this->postJson(route('student.store'), $this->validPayload());

        // Assert
        $response->assertStatus(400);
        $this->assertDatabaseCount('temporary_students', 0);
    }

    /** @return array<string, array{string, string}> */
    public static function duplicateErrorProvider(): array
    {
        return [
            '認証コード' => [CreateCodeService::class, DuplicateAuthorizationCodeException::class],
            '照会用の符号' => [CreateTokenService::class, DuplicateAuthorizationTokenException::class],
        ];
    }

    /** @return array<string, string> */
    private function validPayload(): array
    {
        return [
            'nick_name' => 'test_nick',
            'last_name' => 'test_last',
            'first_name' => 'test_first',
            'email' => 'testtest@example.com',
            'occupation' => 'student',
            'purpose' => 'learning',
            'birth_date' => '2000-01-01',
            'gender' => 'man',
            'address' => '123 Test St',
        ];
    }
}
