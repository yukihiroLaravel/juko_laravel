<?php

namespace Tests\Feature\Api\Manager\CourseDeadline;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClearSelectedTest extends TestCase
{
    use RefreshDatabase;

    /** AC-ENROLL-016 */
    public function test_選択した講座の受講期限だけをクリアする(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $selectedCourse = $this->createCourseWithDeadline($manager);
        $unselectedCourse = $this->createCourseWithDeadline($manager);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => [$selectedCourse->id],
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'result' => true,
            ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => $selectedCourse->id,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $selectedCourse->id,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $unselectedCourse->id,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $unselectedCourse->id,
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
        ]);
    }

    /** AC-ENROLL-016 */
    public function test_複数の講座の受講期限を同時にクリアできる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course1 = $this->createCourseWithDeadline($manager);
        $course2 = $this->createCourseWithDeadline($manager);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => [$course1->id, $course2->id],
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'result' => true,
            ]);
        foreach ([$course1, $course2] as $course) {
            $this->assertDatabaseMissing('course_deadlines', [
                'course_id' => $course->id,
            ]);
            $this->assertDatabaseHas('courses', [
                'id' => $course->id,
                'deadline_type' => DeadlineTypeEnum::NONE->value,
            ]);
        }
    }

    public function test_配下でないマネージャーの講座の受講期限はクリアされない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $otherManager = Instructor::factory()->create();
        $otherCourse = $this->createCourseWithDeadline($otherManager);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => [$otherCourse->id],
        ]);

        // Assert — 対象から外れ、何も変えずに成功を返す
        $response->assertStatus(200);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $otherCourse->id,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $otherCourse->id,
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
        ]);
    }

    public function test_マネージャーでない講師は受講期限をクリアできない(): void
    {
        // Arrange
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    #[DataProvider('invalidCoursesProvider')]
    public function test_講座の選び方が不正なときは入力エラーになる(array $payload, string $errorKey): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), $payload);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([$errorKey]);
    }

    /** @return array<string, array{array<string, mixed>, string}> */
    public static function invalidCoursesProvider(): array
    {
        return [
            '講座を1つも選んでいない' => [['courses' => []], 'courses'],
            '講座の指定がない' => [[], 'courses'],
            '存在しない講座を選んでいる' => [['courses' => [9999]], 'courses.0'],
            '講座の ID が整数でない' => [['courses' => ['invalid']], 'courses.0'],
        ];
    }

    public function test_削除済みの講座を選ぶと入力エラーになる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $course->delete();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('manager.courses.deadline.clear-selected'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    private function createCourseWithDeadline(Instructor $instructor): Course
    {
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
        ]);
        CourseDeadline::factory()->create(['course_id' => $course->id]);

        return $course;
    }
}
