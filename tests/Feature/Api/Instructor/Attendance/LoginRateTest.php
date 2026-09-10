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

    public function test_担当講師はログイン率を取得できる_成功(): void
    {
        // Arrange — 自分の講座のログイン率を取得する
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

    public function test_配下講師の講座はマネージャーがログイン率を取得できる_成功(): void
    {
        // Arrange — マネージャーが配下講師の講座のログイン率を取得する
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

    public function test_担当外の講師はログイン率を取得できない_失敗(): void
    {
        // Arrange — 他の講師の講座を指定する
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

    public function test_配下でないマネージャーはログイン率を取得できない_失敗(): void
    {
        // Arrange — 配下ではない講師の講座を指定する
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

    public function test_バリデーションエラー(): void
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

    public function test_受講生ログイン率取得(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');
        // 期間内にログインした受講生 2人
        Attendance::factory()
            ->count(2)
            ->for(Student::factory()->state(['last_login_at' => now()]), 'student')
            ->create(['course_id' => $course->id]);

        // 期間外（過去だけ）の受講生 1人
        Attendance::factory()
            ->for(Student::factory()->state(['last_login_at' => now()->subMonths(2)]), 'student')
            ->create(['course_id' => $course->id]);

        // 一度もログインしていない受講生 2人
        Attendance::factory()
            ->count(2)
            ->for(Student::factory()->state(['last_login_at' => null]), 'student')
            ->create(['course_id' => $course->id]);

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
