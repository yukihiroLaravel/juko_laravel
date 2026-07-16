<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
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
}
