<?php

namespace Tests\Feature\Api\Instructor\Tag;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_タグ登録_成功(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson('/api/v1/instructor/tag', [
            'content' => 'test',
        ]);

        // assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('tags', [
            'instructor_id' => $instructor->id,
            'content' => 'test',
        ]);
    }

    // public function test_バリデーションエラー(): void
    // {
    //     // arrange
    //     $instructor = Instructor::find(1);
    //     $this->actingAs($instructor, 'instructor');

    //     // act
    //     $response = $this->postJson('/api/v1/instructor', [
    //         'nick_name' => '',
    //         'last_name' => '',
    //         'first_name' => '',
    //         'email' => '',
    //     ]);

    //     // assert
    //     $response->assertStatus(422);
    // }

    // public function test_認証コード重複エラー(): void
    // {
    //     // arrange
    //     $instructor = Instructor::find(1);
    //     $this->actingAs($instructor, 'instructor');
    //     $this->mock(\App\Services\Auth\CredentialGeneratorService::class, function ($mock) {
    //         $mock->shouldReceive('createCode')->andThrow(new \App\Exceptions\DuplicateAuthorizationCodeException('Failed to generate unique authorization code.'));
    //     });

    //     // act
    //     $response = $this->postJson('/api/v1/instructor', [
    //         'nick_name' => 'test',
    //         'last_name' => 'test',
    //         'first_name' => 'test',
    //         'email' => 'testtest@exmaple.com',
    //     ]);

    //     // assert
    //     $response->assertStatus(400);
    // }

    // public function test_トークン重複エラー(): void
    // {
    //     // arrange
    //     $instructor = Instructor::find(1);
    //     $this->actingAs($instructor, 'instructor');
    //     $this->mock(\App\Services\Auth\CredentialGeneratorService::class, function ($mock) {
    //         $mock->shouldReceive('createCode')->andReturn('1234');
    //         $mock->shouldReceive('createToken')->andThrow(new \App\Exceptions\DuplicateAuthorizationTokenException('Failed to generate unique authorization token.'));
    //     });

    //     // act
    //     $response = $this->postJson('/api/v1/instructor', [
    //         'nick_name' => 'test',
    //         'last_name' => 'test',
    //         'first_name' => 'test',
    //         'email' => 'testtest@exmaple.com',
    //     ]);

    //     // assert
    //     $response->assertStatus(400);
    // }
}
