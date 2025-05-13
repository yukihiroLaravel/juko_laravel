<?php

namespace Tests\Feature\Api\Instructor;

use App\Model\TemporaryInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyCodeTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_トークン認証_成功(): void
    {
        // arrange
        $token = 'abcdefghij';
        TemporaryInstructor::create([
            'trial_count' => 0,
            'code' => '1234',
            'token' => $token,
            'expire_at' => now()->addMinutes(10),
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'testtest@example.com',
            'type' => 'instructor',
        ]);

        // act
        $response = $this->postJson('/api/v1/instructor/verification/'.$token, [
            'code' => '1234',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // assert
        $response->assertStatus(200);
    }

    public function test_トークン認証_失敗_期限切れ(): void
    {
        // arrange
        $token = 'abcdefghij';
        TemporaryInstructor::create([
            'trial_count' => 0,
            'code' => '1234',
            'token' => $token,
            'expire_at' => now()->subMinutes(10),
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'testtest@example.com',
            'type' => 'instructor',
        ]);

        // act
        $response = $this->postJson('/api/v1/instructor/verification/'.$token, [
            'code' => '1234',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // assert
        $response->assertStatus(400);
    }

    public function test_トークン認証_失敗_認証コード不一致(): void
    {
        // arrange
        $token = 'abcdefghij';
        TemporaryInstructor::create([
            'trial_count' => 0,
            'code' => '1234',
            'token' => $token,
            'expire_at' => now()->addMinutes(10),
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'testtest@example.com',
            'type' => 'instructor',
        ]);

        // act
        $response = $this->postJson('/api/v1/instructor/verification/'.$token, [
            'code' => '0000',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // assert
        $response->assertStatus(400);
    }

    public function test_トークン認証_失敗_試行回数超過(): void
    {
        // arrange
        $token = 'abcdefghij';
        TemporaryInstructor::create([
            'trial_count' => 3,
            'code' => '1234',
            'token' => $token,
            'expire_at' => now()->addMinutes(10),
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'testtest@example.com',
            'type' => 'instructor',
        ]);

        // act
        $response = $this->postJson('/api/v1/instructor/verification/'.$token, [
            'code' => '0000',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // assert
        $response->assertStatus(400);
        $this->assertEmpty(TemporaryInstructor::all());
    }
}
