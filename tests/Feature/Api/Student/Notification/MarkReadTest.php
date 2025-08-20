<?php

namespace tests\Feature\Api\Student\Notification;

use App\Model\Course;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkReadTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせ既読登録成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student, 'web');

        // act
        $response = $this->postJson('/api/v1/notification/mark-read', [
            'notification_id' => 4,
        ]);

        // assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
        ]);
        $this->assertDatabaseHas('viewed_once_notifications', [
            'notification_id' => 4,
            'student_id' => $student->id,
        ]);
    }

    public function test_期限切れのお知らせは既読登録できない(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student, 'web');
        Course::find(1)->update([
            'attendance_deadline' => now()->subDays(1), // 期限切れに設定
        ]);

        // act
        $response = $this->postJson('/api/v1/notification/mark-read', [
            'notification_id' => 4,
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'The course has expired.',
        ]);
        $this->assertDatabaseMissing('viewed_once_notifications', [
            'notification_id' => 4,
            'student_id' => $student->id,
        ]);
    }
}
