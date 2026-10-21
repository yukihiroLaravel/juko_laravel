<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_マネージャーで講座削除する(): void
    {
        // Arrange — マネージャー自身の講座（受講生なし）
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.delete', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('courses', [
            'id' => $course->id,
        ]);
    }

    /** AC-AUTHZ-002 */
    public function test_マネージャーで配下の講師の講座削除する(): void
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
        $response = $this->deleteJson(route('instructor.courses.delete', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('courses', [
            'id' => $course->id,
        ]);
    }

    /** AC-AUTHOR-016 */
    public function test_講座を削除するとチャプターとレッスンと受講期限も削除される(): void
    {
        // Arrange — 受講生がいない講座
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        CourseDeadline::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.delete', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $this->assertSoftDeleted('courses', ['id' => $course->id]);
        $this->assertSoftDeleted('chapters', ['id' => $chapter->id]);
        $this->assertSoftDeleted('lessons', ['id' => $lesson->id]);
        $this->assertDatabaseMissing('course_deadlines', ['course_id' => $course->id]);
    }

    /** AC-AUTHZ-001, AC-AUTHZ-003 */
    #[DataProvider('instructorTypeProvider')]
    public function test_担当でも配下でもない講師の講座は削除できない(string $type): void
    {
        // Arrange — 操作者の担当でも配下でもない講師の講座
        $operator = Instructor::factory()->create(['type' => $type]);
        $otherInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($operator, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.delete', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function instructorTypeProvider(): array
    {
        return [
            'マネージャー' => ['manager'],
            '講師' => ['instructor'],
        ];
    }

    /** AC-AUTHOR-015 */
    public function test_受講生がいる講座は削除できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.delete', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This course has already been taken by students.',
        ]);
    }

    public function test_講座の指定が数値でないと削除できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.delete', ['course_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }

    /** AC-COMMON-015 */
    public function test_削除の途中で失敗するとチャプターとレッスンと受講期限も元のまま残る(): void
    {
        // Arrange — 子を消したあと、講座を消すクエリの直後に失敗させる
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        CourseDeadline::factory()->create(['course_id' => $course->id]);
        $this->failAfterQuery('update "courses"');
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->deleteJson(route('instructor.courses.delete', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(500);
        $this->assertNotSoftDeleted('courses', ['id' => $course->id]);
        $this->assertNotSoftDeleted('chapters', ['id' => $chapter->id]);
        $this->assertNotSoftDeleted('lessons', ['id' => $lesson->id]);
        $this->assertDatabaseHas('course_deadlines', ['course_id' => $course->id]);
    }
}
