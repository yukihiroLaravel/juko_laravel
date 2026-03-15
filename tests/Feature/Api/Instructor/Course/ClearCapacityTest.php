<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearCapacityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 正常系: 選択した講座の定員をなくせること
     */
    public function test_選択した講座の定員をなくせること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $courses = Course::factory()->count(3)->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.course.capacity.clear'), [
            'courses' => $courses->pluck('id')->toArray(),
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 3,
        ]);

        // DBのcapacityがnullになっていること
        $courses->each(function (Course $course) {
            $this->assertDatabaseHas('courses', [
                'id' => $course->id,
                'capacity' => null,
            ]);
        });
    }

    /**
     * 異常系: 受講者が存在する講座が含まれている場合はエラーになること
     */
    public function test_受講者が存在する講座が含まれている場合はエラーになること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);

        // 受講者を紐付け
        Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.course.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    /**
     * 異常系: 他講師の講座は変更できないこと
     */
    public function test_他講師の講座は変更できないこと(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create();

        $course = Course::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'capacity' => 10,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.course.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(403);
    }

    /**
     * 異常系: coursesが空の場合はバリデーションエラーになること
     */
    public function test_coursesが空の場合はバリデーションエラーになること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.course.capacity.clear'), [
            'courses' => [],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    /**
     * 異常系: 未認証の場合はエラーになること
     */
    public function test_未認証の場合はエラーになること(): void
    {
        // Arrange
        $course = Course::factory()->create(['capacity' => 10]);

        // Act
        $response = $this->patchJson(route('instructor.course.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(401);
    }
}
