<?php

namespace Tests\Feature\Api\Instructor;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Model\Instructor;
use App\Services\Auth\CreateCodeService;
use App\Services\Auth\CreateTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_仮講師登録_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.register'), [
            'nick_name' => 'test',
            'last_name' => 'test',
            'first_name' => 'test',
            'email' => 'testtest@exmaple.com',
        ]);

        // Assert
        $response->assertStatus(200);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

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

    public function test_認証コード重複エラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');
        $this->mock(CreateCodeService::class, function ($mock) {
            $mock->shouldReceive('__invoke')->andThrow(new DuplicateAuthorizationCodeException('Failed to generate unique authorization code.'));
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
    }

    public function test_トークン重複エラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');
        $this->mock(CreateTokenService::class, function ($mock) {
            $mock->shouldReceive('__invoke')->andThrow(new DuplicateAuthorizationTokenException('Failed to generate unique authorization token.'));
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
    }
}
