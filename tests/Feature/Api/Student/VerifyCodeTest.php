<?php

namespace Tests\Feature\Api\Student;

use App\Model\TemporaryStudent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class VerifyCodeTest extends TestCase
{
    use RefreshDatabase;

    /** AC-ACCOUNT-015 */
    public function test_認証コードが一致すると本登録され仮登録が消える(): void
    {
        // Arrange
        $temporaryStudent = TemporaryStudent::factory()->create([
            'code' => '1234',
        ]);

        // Act
        $response = $this->verifyCode($temporaryStudent, '1234');

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('students', ['email' => $temporaryStudent->email]);
        $this->assertDatabaseMissing('temporary_students', ['id' => $temporaryStudent->id]);
    }

    /** AC-ACCOUNT-012 */
    public function test_認証コードの有効期間を過ぎると400になり仮登録が消える(): void
    {
        // Arrange
        $temporaryStudent = TemporaryStudent::factory()->create([
            'code' => '1234',
            'expire_at' => now()->subMinutes(10),
        ]);

        // Act
        $response = $this->verifyCode($temporaryStudent, '1234');

        // Assert
        $response->assertStatus(400);
        $this->assertDatabaseMissing('temporary_students', ['id' => $temporaryStudent->id]);
        $this->assertDatabaseMissing('students', ['email' => $temporaryStudent->email]);
    }

    /** AC-ACCOUNT-013 */
    public function test_認証コードが一致しないと400になり試行回数が1増える(): void
    {
        // Arrange
        $temporaryStudent = TemporaryStudent::factory()->create([
            'code' => '1234',
        ]);

        // Act
        $response = $this->verifyCode($temporaryStudent, '0000');

        // Assert
        $response->assertStatus(400);
        $this->assertDatabaseHas('temporary_students', [
            'id' => $temporaryStudent->id,
            'trial_count' => 1,
        ]);
    }

    /** AC-ACCOUNT-014 */
    public function test_試行回数が3回を超えると400になり仮登録が消える(): void
    {
        // Arrange
        $temporaryStudent = TemporaryStudent::factory()->create([
            'trial_count' => 3,
            'code' => '1234',
        ]);

        // Act
        $response = $this->verifyCode($temporaryStudent, '0000');

        // Assert
        $response->assertStatus(400);
        $this->assertEmpty(TemporaryStudent::all());
    }

    private function verifyCode(TemporaryStudent $temporaryStudent, string $code): TestResponse
    {
        return $this->postJson(
            route('student.verify-code', ['token' => $temporaryStudent->token]),
            [
                'code' => $code,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        );
    }
}
