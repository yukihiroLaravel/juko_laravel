<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRateTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHZ-001 */
    public function test_担当講師はログイン率を取得できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.login-rate', [
            'course_id' => $course->id,
            'period' => 'week',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    /** AC-AUTHZ-002 */
    public function test_配下講師の講座はマネージャーがログイン率を取得できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.login-rate', [
            'course_id' => $course->id,
            'period' => 'week',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    /** AC-AUTHZ-001 */
    public function test_担当外の講師はログイン率を取得できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.login-rate', [
            'course_id' => $course->id,
            'period' => 'week',
        ]));

        // Assert
        $response->assertStatus(403);
    }

    /** AC-AUTHZ-003 */
    public function test_配下でないマネージャーはログイン率を取得できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherManager = Instructor::factory()->create();
        $this->actingAs($otherManager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.login-rate', [
            'course_id' => $course->id,
            'period' => 'week',
        ]));

        // Assert
        $response->assertStatus(403);
    }

    /** AC-ANALYTICS-005 */
    public function test_講座と期間の指定が不正だと422になる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.login-rate', [
            'course_id' => 'aaa',
            'period' => 'bbb',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'period',
        ]);
    }

    /** AC-ANALYTICS-004・AC-ANALYTICS-006 */
    public function test_期間内にログインした受講生の割合を返す(): void
    {
        // Arrange
        // 一度もログインしていない受講生も分母に含める（Q-005 の暫定方針）
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');
        // 期間内にログインした受講生 2人
        Student::factory()
            ->count(2)
            ->has(Attendance::factory()->state(['course_id' => $course->id]), 'attendances')
            ->create(['last_login_at' => now()]);

        // 期間外（過去だけ）の受講生 1人
        Student::factory()
            ->has(Attendance::factory()->state(['course_id' => $course->id]), 'attendances')
            ->create(['last_login_at' => now()->subMonths(2)]);

        // 一度もログインしていない受講生 2人
        Student::factory()
            ->count(2)
            ->has(Attendance::factory()->state(['course_id' => $course->id]), 'attendances')
            ->create(['last_login_at' => null]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.login-rate', [
            'course_id' => $course->id,
            'period' => 'week',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('login_rate', 40);
    }
}
