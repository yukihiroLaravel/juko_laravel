<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\Student;
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
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
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
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);

        // capacityが変更されていないこと
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'capacity' => 10,
        ]);
    }

    /**
     * 異常系: 受講者ありの講座と受講者なしの講座が混在する場合はエラーになること
     */
    public function test_一部に受講者が存在する講座を含む場合はエラーになること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $courseWithoutStudents = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);
        $courseWithStudents = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);

        $student = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $courseWithStudents->id,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$courseWithoutStudents->id, $courseWithStudents->id],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);

        // どちらのcapacityも変更されていないこと（トランザクションでロールバック）
        $this->assertDatabaseHas('courses', [
            'id' => $courseWithoutStudents->id,
            'capacity' => 10,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $courseWithStudents->id,
            'capacity' => 10,
        ]);
    }

    /**
     * 正常系: マネージャーが自分の講座の定員をクリアできること
     */
    public function test_マネージャーが自分の講座の定員をクリアできること(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
            'capacity' => 10,
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'capacity' => null,
        ]);
    }

    /**
     * 正常系: マネージャーが配下の講師の講座の定員をクリアできること
     */
    public function test_マネージャーが配下の講師の講座の定員をクリアできること(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);

        $course = Course::factory()->create([
            'instructor_id' => $subordinate->id,
            'capacity' => 10,
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'result' => true,
            'updated_count' => 1,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'capacity' => null,
        ]);
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
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    /**
     * 異常系: 権限がないマネージャーの場合変更できないこと
     */
    public function test_権限がないマネージャーの場合変更できないこと(): void
    {
        // Arrange — 配下にない講師の講座
        $manager = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create();

        $course = Course::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'capacity' => 10,
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    /**
     * 異常系: 一部に権限がない講座を含む場合変更できないこと
     */
    public function test_一部に権限がない講座を含む場合変更できないこと(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $ownCourse = Course::factory()->create([
            'instructor_id' => $manager->id,
            'capacity' => 10,
        ]);
        $otherInstructor = Instructor::factory()->create();
        $otherCourse = Course::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'capacity' => 10,
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$ownCourse->id, $otherCourse->id],
        ]);

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);

        // どちらのcapacityも変更されていないこと
        $this->assertDatabaseHas('courses', [
            'id' => $ownCourse->id,
            'capacity' => 10,
        ]);
    }

    /**
     * 異常系: 講座が空の場合はバリデーションエラーになること
     */
    public function test_講座が空の場合はバリデーションエラーになること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    /**
     * 異常系: 講座が未送信の場合はバリデーションエラーになること
     */
    public function test_講座が未送信の場合はバリデーションエラーになること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), []);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    /**
     * 異常系: 講座が配列でない場合はバリデーションエラーになること
     */
    public function test_講座が配列でない場合はバリデーションエラーになること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => 'abc',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    /**
     * 異常系:
     */
    public function test_講座が整数でない場合はバリデーションエラーになること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => ['abc'],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    /**
     * 異常系: 存在しない講座を含む場合はバリデーションエラーになること
     */
    public function test_存在しない講座を含む場合はバリデーションエラーになること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [99999],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    /**
     * 異常系: 削除済みの講座を含む場合はバリデーションエラーになること
     */
    public function test_削除済みの講座を含む場合はバリデーションエラーになること(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);
        $course->delete();

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    /**
     * 異常系: 未認証の場合はエラーになること
     */
    public function test_未認証の場合はエラーになること(): void
    {
        // Arrange
        $course = Course::factory()->create(['capacity' => 10]);

        // Act
        $response = $this->patchJson(route('instructor.courses.capacity.clear'), [
            'courses' => [$course->id],
        ]);

        // Assert
        $response->assertStatus(401);
    }
}
