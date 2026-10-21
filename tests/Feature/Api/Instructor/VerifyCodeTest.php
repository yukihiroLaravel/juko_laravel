<?php

namespace Tests\Feature\Api\Instructor;

use App\Model\TemporaryInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyCodeTest extends TestCase
{
    use RefreshDatabase;

    /** AC-ACCOUNT-015 */
    public function test_認証コードが一致すると講師として本登録され仮登録が消える(): void
    {
        // Arrange
        $temporaryInstructor = TemporaryInstructor::factory()->create([
            'code' => '1234',
            'expire_at' => now()->addMinutes(10),
        ]);

        // Act
        $response = $this->postJson(
            route('instructor.verify-code', ['token' => $temporaryInstructor->token]),
            [
                'code' => '1234',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        );

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('instructors', ['email' => $temporaryInstructor->email]);
        $this->assertEmpty(TemporaryInstructor::all());
    }

    /** AC-ACCOUNT-012 */
    public function test_認証コードの有効期間を過ぎていると本登録できず仮登録が消える(): void
    {
        // Arrange
        $temporaryInstructor = TemporaryInstructor::factory()->create([
            'code' => '1234',
            'expire_at' => now()->subMinutes(10),
        ]);

        // Act
        $response = $this->postJson(
            route('instructor.verify-code', ['token' => $temporaryInstructor->token]),
            [
                'code' => '1234',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        );

        // Assert
        $response->assertStatus(400);
        $this->assertEmpty(TemporaryInstructor::all());
    }

    /** AC-ACCOUNT-013 */
    public function test_認証コードが一致しないと本登録できない(): void
    {
        // Arrange
        $temporaryInstructor = TemporaryInstructor::factory()->create([
            'code' => '1234',
            'expire_at' => now()->addMinutes(10),
        ]);

        // Act
        $response = $this->postJson(
            route('instructor.verify-code', ['token' => $temporaryInstructor->token]),
            [
                'code' => '0000',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        );

        // Assert
        $response->assertStatus(400);
    }

    /** AC-ACCOUNT-014 */
    public function test_認証コードの試行回数が上限を超えると本登録できず仮登録が消える(): void
    {
        // Arrange
        $temporaryInstructor = TemporaryInstructor::factory()->create([
            'trial_count' => 3,
            'code' => '1234',
            'expire_at' => now()->addMinutes(10),
        ]);

        // Act
        $response = $this->postJson(
            route('instructor.verify-code', ['token' => $temporaryInstructor->token]),
            [
                'code' => '0000',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        );

        // Assert
        $response->assertStatus(400);
        $this->assertEmpty(TemporaryInstructor::all());
    }
}
