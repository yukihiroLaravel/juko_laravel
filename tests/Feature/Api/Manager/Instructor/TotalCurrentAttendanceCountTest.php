<?php

namespace Tests\Feature\Api\Manager\Instructor;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TotalCurrentAttendanceCountTest extends TestCase
{
    use RefreshDatabase;

    /** AC-ANALYTICS-045 */
    public function test_配下の講師の受講中の受講生数は修了していない受講だけを数える(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);

        $course1 = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $course2 = Course::factory()->create(['instructor_id' => $subordinate->id]);

        // 修了していない受講（カウント対象）
        Attendance::factory()->count(2)->create([
            'course_id' => $course1->id,
            'completed_at' => null,
        ]);
        Attendance::factory()->create([
            'course_id' => $course2->id,
            'completed_at' => null,
        ]);

        // 修了済みの受講（カウント対象外）
        Attendance::factory()->create([
            'course_id' => $course1->id,
            'completed_at' => CarbonImmutable::now(),
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.total-current-attendance-count', [
            'instructor_id' => $subordinate->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'total_current_attendance_count' => 3,
            ],
        ]);
    }

    /** AC-ANALYTICS-045 */
    public function test_ほかの講師の講座の受講は受講中の受講生数に含めない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);

        $subordinateCourse = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $otherCourse = Course::factory()->create(['instructor_id' => $otherInstructor->id]);

        Attendance::factory()->create([
            'course_id' => $subordinateCourse->id,
            'completed_at' => null,
        ]);
        Attendance::factory()->create([
            'course_id' => $otherCourse->id,
            'completed_at' => null,
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.total-current-attendance-count', [
            'instructor_id' => $subordinate->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.total_current_attendance_count', 1);
    }

    public function test_配下でない講師の受講中の受講生数は取得できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.total-current-attendance-count', [
            'instructor_id' => $otherInstructor->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_マネージャーでない講師は受講中の受講生数を取得できない(): void
    {
        // Arrange
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.total-current-attendance-count', [
            'instructor_id' => 1,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_存在しない講師を指定すると入力エラーになる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.total-current-attendance-count', [
            'instructor_id' => 99999,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['instructor_id']);
    }
}
