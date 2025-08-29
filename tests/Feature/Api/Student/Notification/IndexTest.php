<?php

namespace Tests\Feature\Api\Student\Notification;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_お知らせ一覧取得_成功(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student, 'web');

        // act
        $response = $this->getJson('/api/v1/notification/index');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.notifications');
    }

    public function test_固定期限切れのお知らせは取得しない(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student, 'web');
        Course::find(1)->update(['deadline_type' => DeadlineTypeEnum::FIXED_DATE->value]);
        Attendance::find(1)->update(['attendance_deadline' => now()->subDay()]);

        // act
        $response = $this->getJson('/api/v1/notification/index');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data.notifications');
    }

    public function test_相対期限切れのお知らせは取得しない(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student, 'web');
        Course::find(1)->update(['deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value]);
        Attendance::find(1)->update(['attendance_deadline' => now()->subDay()]);

        // act
        $response = $this->getJson('/api/v1/notification/index');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data.notifications');
    }

    public function test_期限がない講座のお知らせは取得する(): void
    {
        // arrange
        $student = Student::find(1);
        $this->actingAs($student, 'web');

        // act
        $response = $this->getJson('/api/v1/notification/index');

        // assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.notifications');
    }
}
