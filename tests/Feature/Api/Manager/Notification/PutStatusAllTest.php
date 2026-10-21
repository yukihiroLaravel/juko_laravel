<?php

namespace Tests\Feature\Api\Manager\Notification;

use App\Model\Instructor;
use App\Model\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PutStatusAllTest extends TestCase
{
    use RefreshDatabase;

    /** AC-NOTIF-011 */
    #[DataProvider('statusChangeProvider')]
    public function test_お知らせの公開状態を一括で変更できる(string $before, string $after): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $notification1 = Notification::factory()->create([
            'instructor_id' => $manager->id,
            'status' => $before,
        ]);
        $notification2 = Notification::factory()->create([
            'instructor_id' => $manager->id,
            'status' => $before,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.put-status-all'), [
            'status' => $after,
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification1->id,
            'status' => $after,
        ]);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification2->id,
            'status' => $after,
        ]);
    }

    /** @return array<string, array{string, string}> */
    public static function statusChangeProvider(): array
    {
        return [
            '非公開から公開へ' => ['private', 'public'],
            '公開から非公開へ' => ['public', 'private'],
        ];
    }

    public function test_公開状態が空のときは入力エラーになる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.put-status-all'), [
            'status' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'status' => 'The status field is required.',
        ]);
    }

    public function test_公開状態に無効な値を指定すると入力エラーになる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.put-status-all'), [
            'status' => 'invalid_status',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The selected status is invalid.',
        ]);
    }

    public function test_マネージャーでない講師はお知らせの公開状態を一括変更できない(): void
    {
        // Arrange
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->putJson(route('manager.notifications.put-status-all'));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_ログインしていないときは認証エラーになる(): void
    {
        // Act
        $response = $this->putJson(route('manager.notifications.put-status-all'));

        // Assert
        $response->assertStatus(401);
    }
}
