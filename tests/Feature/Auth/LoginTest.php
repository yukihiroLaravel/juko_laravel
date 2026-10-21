<?php

namespace Tests\Feature\Auth;

use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン_成功(): void
    {
        // Arrange
        Student::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'message' => 'Authenticated.',
        ]);
    }

    /** AC-ACCOUNT-022 */
    public function test_ログイン成功時_ログイン履歴が記録され最終ログイン日時が更新される(): void
    {
        // Arrange
        $student = Student::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'last_login_at' => null,
        ]);

        // Act
        $this->postJson(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $this->assertDatabaseHas('student_login_histories', [
            'student_id' => $student->id,
        ]);
        $student->refresh();
        $this->assertNotNull($student->last_login_at);
    }

    /** AC-ACCOUNT-021 */
    #[DataProvider('invalidCredentialsProvider')]
    public function test_認証に失敗すると401になる(string $email, string $password): void
    {
        // Arrange
        Student::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson(route('login'), [
            'email' => $email,
            'password' => $password,
        ]);

        // Assert
        $response->assertStatus(401);
    }

    /** @return array<string, array{string, string}> */
    public static function invalidCredentialsProvider(): array
    {
        return [
            'パスワード不一致' => ['test@example.com', 'wrongpassword'],
            '存在しないメールアドレス' => ['nonexistent@example.com', 'password123'],
        ];
    }

    #[DataProvider('invalidInputProvider')]
    public function test_入力が不正だとバリデーションエラーになる(string $email, string $password, string $invalidField): void
    {
        // Act
        $response = $this->postJson(route('login'), [
            'email' => $email,
            'password' => $password,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([$invalidField]);
    }

    /** @return array<string, array{string, string, string}> */
    public static function invalidInputProvider(): array
    {
        return [
            'メールアドレス未入力' => ['', 'password123', 'email'],
            'パスワード未入力' => ['test@example.com', '', 'password'],
            'メールアドレス形式不正' => ['invalid-email', 'password123', 'email'],
            'パスワード6文字未満' => ['test@example.com', '12345', 'password'],
        ];
    }
}
