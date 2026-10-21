<?php

namespace Tests\Feature\Api\Manager\Attendance;

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

    /** AC-AUTHZ-002 */
    public function test_配下の講師の講座のログイン率をマネージャーが取得できる(): void
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
        $response = $this->getJson(route('manager.courses.attendances.login-rate', [
            'course_id' => $course->id,
            'period' => 'week',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    /** AC-AUTHZ-003 */
    public function test_配下でないマネージャーの講座のログイン率は取得できない(): void
    {
        // Arrange — 別のマネージャーの講座のログイン率を取得しようとする
        $otherManager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherManager->id]);
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.login-rate', [
            'course_id' => $course->id,
            'period' => 'week',
        ]));

        // Assert
        $response->assertStatus(403);
    }

    /** AC-ANALYTICS-005 */
    public function test_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.login-rate', [
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
    public function test_期間内にログインした受講生の割合をログイン率として返す(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');
        // 期間内にログインした受講生 2人
        Student::factory()
            ->count(2)
            ->has(Attendance::factory()->state(['course_id' => $course->id]), 'attendances')
            ->create(['last_login_at' => now()]);

        // 期間外（過去だけ）の受講生 1人
        Student::factory()
            ->has(Attendance::factory()->state(['course_id' => $course->id]), 'attendances')
            ->create(['last_login_at' => now()->subMonths(2)]);

        // 一度もログインしていない受講生 2人。Q-005 の暫定方針により分母に含める
        Student::factory()
            ->count(2)
            ->has(Attendance::factory()->state(['course_id' => $course->id]), 'attendances')
            ->create(['last_login_at' => null]);

        // Act
        $response = $this->getJson(route('manager.courses.attendances.login-rate', [
            'course_id' => $course->id,
            'period' => 'week',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('login_rate', 40);
    }
}
