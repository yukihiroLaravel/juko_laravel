<?php

namespace Tests\Feature\Api\Manager\Attendance;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン率取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.login-rate', [
            'course_id' => $course->id,
            'period' => 'week',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_講師が一致しない_失敗(): void
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

    public function test_受講生ログイン率取得(): void
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

        // 一度もログインしていない受講生 2人
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
