<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Enums\Notification\TypeEnum;
use App\Model\Course;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    /** AC-NOTIF-001, AC-NOTIF-007 */
    #[DataProvider('instructorTypeProvider')]
    public function test_講座を指定してお知らせを登録できる(string $type): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => $type]);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.notifications.store', ['course_id' => $course->id]), [
            'title' => 'title',
            'type' => 'always',
            'start_date' => '2022-01-01 00:00:00',
            'end_date' => '2022-01-02 00:00:00',
            'content' => 'content',
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', [
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'title' => 'title',
            'type' => TypeEnum::ALWAYS,
            'start_date' => '2022-01-01 00:00:00',
            'end_date' => '2022-01-02 00:00:00',
            'content' => 'content',
            'status' => 'private',
        ]);
    }

    /** AC-AUTHZ-004 */
    public function test_他の講師の講座にはお知らせを登録できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.notifications.store', ['course_id' => $course->id]), [
            'title' => 'title',
            'type' => 'always',
            'start_date' => '2022-01-01 00:00:00',
            'end_date' => '2022-01-02 00:00:00',
            'content' => 'content',
            'status' => 'private',
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_入力がすべて空のときはお知らせを登録できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.notifications.store', ['course_id' => 'aaa']), [
            'title' => '',
            'type' => '',
            'start_date' => '',
            'end_date' => '',
            'content' => '',
            'status' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'title',
            'type',
            'start_date',
            'end_date',
            'content',
            'status',
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function instructorTypeProvider(): array
    {
        return [
            'マネージャー' => ['manager'],
            '講師' => ['instructor'],
        ];
    }
}
