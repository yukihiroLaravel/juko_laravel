<?php

namespace Tests\Feature\Api\Instructor;

use App\Model\TemporaryInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    /** AC-ACCOUNT-005 */
    public function test_仮登録では4文字の認証コードと10文字の照会用の符号を発行する(): void
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
        $temporaryInstructor = TemporaryInstructor::where('email', 'testtest@exmaple.com')->sole();
        $this->assertSame(4, strlen($temporaryInstructor->code));
        $this->assertSame(10, strlen($temporaryInstructor->token));
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
    public function test_既存の仮登録と重ならない値を生成できないときは仮登録できない(array $existingValues): void
    {
        // Arrange — 生成する値を毎回同じにして、既存の仮登録と必ず重なるようにする
        TemporaryInstructor::factory()->create($existingValues);
        Str::createRandomStringsUsing(fn (int $length) => str_repeat('a', $length));

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
