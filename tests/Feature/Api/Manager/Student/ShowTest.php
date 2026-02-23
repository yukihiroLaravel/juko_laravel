<?php

namespace Tests\Feature\Api\Manager\Student;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_生徒取得_成功(): void
    {
        // Arrange — マネージャーの講座に受講している生徒
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $student = Student::factory()->create([
            'last_login_at' => now(),
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.student.show', ['student_id' => $student->id]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_許可がない講師_失敗(): void
    {
        // Arrange — 別のマネージャーの講座の生徒を取得しようとする
        $ownerManager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerManager->id]);
        $student = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $otherManager = Instructor::factory()->create();
        $this->actingAs($otherManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.student.show', ['student_id' => $student->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.student.show', ['student_id' => 'bbb']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'student_id',
        ]);
    }

    public function test_権限エラー(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.student.show', ['student_id' => 'bbb']));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }
}
