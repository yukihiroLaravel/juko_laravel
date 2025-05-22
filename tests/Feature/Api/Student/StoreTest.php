<?php

namespace Tests\Feature\Api\Student;

use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    // setup
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_仮生徒登録(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->postJson('/api/v1/student', [
            'nick_name' => 'test_nick',
            'last_name' => 'test_last',
            'first_name' => 'test_first',
            'email' => 'testtest@example.com',
            'occupation' => 'student',
            'purpose' => 'learning',
            'birth_date' => '2000-01-01',
            'gender' => 'man',
            'address' => '123 Test St',
        ]);

        // assert
        $response->assertStatus(200);
    }

    public function test_バリデーションエラー(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        // act
        $response = $this->postJson('/api/v1/student', [
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

        // assert
        $response->assertStatus(422);
    }

    public function test_認証コード重複エラー(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        $this->mock(\App\Services\Auth\CredentialGeneratorService::class, function ($mock) {
            $mock->shouldReceive('createCode')->andThrow(new \App\Exceptions\DuplicateAuthorizationCodeException('Failed to generate unique authorization code.'));
        });

        // act
        $response = $this->postJson('/api/v1/student', [
            'nick_name' => 'test_nick',
            'last_name' => 'test_last',
            'first_name' => 'test_first',
            'email' => 'testtest@example.com',
            'occupation' => 'student',
            'purpose' => 'learning',
            'birth_date' => '2000-01-01',
            'gender' => 'man',
            'address' => '123 Test St',
        ]);

        // assert
        $response->assertStatus(400);
    }

    public function test_トークン重複エラー(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student);

        $this->mock(\App\Services\Auth\CredentialGeneratorService::class, function ($mock) {
            $mock->shouldReceive('createCode')->andReturn('1234');
            $mock->shouldReceive('createToken')->andThrow(new \App\Exceptions\DuplicateAuthorizationTokenException('Failed to generate unique authorization token.'));
        });

        // act
        $response = $this->postJson('/api/v1/student', [
            'nick_name' => 'test_nick',
            'last_name' => 'test_last',
            'first_name' => 'test_first',
            'email' => 'testtest@example.com',
            'occupation' => 'student',
            'purpose' => 'learning',
            'birth_date' => '2000-01-01',
            'gender' => 'man',
            'address' => '123 Test St',
        ]);

        // assert
        $response->assertStatus(400);
    }
}
