<?php

namespace Tests\Feature\Api\Student;

use App\Model\TemporaryStudent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    /** AC-ACCOUNT-005 */
    public function test_仮登録では4文字の認証コードと10文字の照会用の符号を発行する(): void
    {
        // Arrange
        $payload = $this->validPayload();

        // Act
        $response = $this->postJson(route('student.store'), $payload);

        // Assert
        $response->assertStatus(200);
        $temporaryStudent = TemporaryStudent::where('email', $payload['email'])->sole();
        $this->assertSame(4, strlen($temporaryStudent->code));
        $this->assertSame(10, strlen($temporaryStudent->token));
    }

    /**
     * 既存の仮登録と重なる値
     *
     * @return array<string, array{array<string, string>}>
     */
    public static function duplicatedValues(): array
    {
        return [
            '認証コードが重なる' => [['code' => 'aaaa']],
            '照会用の符号が重なる' => [['token' => 'aaaaaaaaaa']],
        ];
    }

    /**
     * AC-ACCOUNT-006
     *
     * @param  array<string, string>  $existingValues
     */
    #[DataProvider('duplicatedValues')]
    public function test_既存の仮登録と重ならない値を生成できないときは400になり仮登録を作らない(array $existingValues): void
    {
        // Arrange — 生成する値を毎回同じにして、既存の仮登録と必ず重なるようにする
        TemporaryStudent::factory()->create($existingValues);
        Str::createRandomStringsUsing(fn (int $length) => str_repeat('a', $length));
        $payload = $this->validPayload();

        // Act
        $response = $this->postJson(route('student.store'), $payload);

        // Assert
        $response->assertStatus(400);
        $this->assertDatabaseMissing('temporary_students', ['email' => $payload['email']]);
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
