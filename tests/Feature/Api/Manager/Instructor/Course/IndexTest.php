<?php

namespace Tests\Feature\Api\Manager\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_配下の講師の講座一覧を取得できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        Course::factory()->count(2)->create(['instructor_id' => $subordinate->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.courses.index', ['instructor_id' => $subordinate->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_配下でない講師の講座一覧は取得できない(): void
    {
        // Arrange
        $otherManager = Instructor::factory()->create();
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.courses.index', ['instructor_id' => $instructor->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, invalid instructor_id.',
        ]);
    }

    #[DataProvider('capacityAndAttendanceProvider')]
    public function test_講座一覧に定員と受講人数が含まれる(?int $capacity, int $attendanceCount, ?int $expectedAttendanceCount): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create([
            'instructor_id' => $subordinate->id,
            'capacity' => $capacity,
        ]);
        Attendance::factory()->count($attendanceCount)->create(['course_id' => $course->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.courses.index', ['instructor_id' => $subordinate->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.capacity', $capacity);
        $response->assertJsonPath('data.0.current_attendance_count', $expectedAttendanceCount);
    }

    /** @return array<string, array{int|null, int, int|null}> */
    public static function capacityAndAttendanceProvider(): array
    {
        return [
            '定員ありで受講あり' => [10, 3, 3],
            '定員なしで受講なし' => [null, 0, null],
            '定員なしで受講あり' => [null, 1, null],
        ];
    }

    public function test_講師の指定が整数でないときは入力エラーになる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.courses.index', ['instructor_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'instructor_id',
        ]);
    }
}
