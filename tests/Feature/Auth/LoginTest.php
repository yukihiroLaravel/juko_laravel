<?php

namespace Tests\Feature\Auth;

use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_ログイン成功時_ログイン履歴が記録される(): void
    {
        // Arrange
        $student = Student::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
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
    }

    public function test_ログイン成功時_最終ログイン日時が更新される(): void
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
        $student->refresh();
        $this->assertNotNull($student->last_login_at);
    }

    public function test_パスワード不一致_ログイン失敗(): void
    {
        // Arrange
        Student::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson(route('login'), [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Assert
        $response->assertStatus(401);
    }

    public function test_存在しないメールアドレス_ログイン失敗(): void
    {
        // Arrange
        Student::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Act
        $response = $this->postJson(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(401);
    }

    public function test_バリデーションエラー_メールアドレス未入力(): void
    {
        // Act
        $response = $this->postJson(route('login'), [
            'email' => '',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_バリデーションエラー_パスワード未入力(): void
    {
        // Act
        $response = $this->postJson(route('login'), [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }

    public function test_バリデーションエラー_メールアドレス形式不正(): void
    {
        // Act
        $response = $this->postJson(route('login'), [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_バリデーションエラー_パスワード6文字未満(): void
    {
        // Act
        $response = $this->postJson(route('login'), [
            'email' => 'test@example.com',
            'password' => '12345',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    }
}
