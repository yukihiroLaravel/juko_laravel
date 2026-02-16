<?php

namespace Tests\Feature\Api\Manager\CourseDeadline;

use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClearSelectedTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_選択した講座の受講期限をクリアできる(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson(route('manager.course.deadline.clear-selected'), [
            'courses' => [2],
        ]);

        // assert
        $response->assertStatus(200)
            ->assertJson([
                'result' => true,
            ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => 2,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => 2,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
    }

    public function test_選択していない講座の受講期限はクリアされない(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson(route('manager.course.deadline.clear-selected'), [
            'courses' => [2],
        ]);

        // assert
        $response->assertStatus(200);

        // 講座3はクリアされていない
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => 3,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => 3,
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
        ]);
    }

    public function test_複数の講座を同時にクリアできる(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson(route('manager.course.deadline.clear-selected'), [
            'courses' => [2, 3],
        ]);

        // assert
        $response->assertStatus(200)
            ->assertJson([
                'result' => true,
            ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => 2,
        ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => 3,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => 2,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
        $this->assertDatabaseHas('courses', [
            'id' => 3,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
    }

    public function test_他マネージャーの講座は対象外となる(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // 講座4はマネージャー4（instructor_id=4）の講座なのでマネージャー1は操作できない
        // act
        $response = $this->postJson(route('manager.course.deadline.clear-selected'), [
            'courses' => [4],
        ]);

        // assert
        // サービスは冪等性があるため成功を返すが、変更は行われない
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'id' => 4,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
        ]);
    }

    public function test_マネージャーでない場合は403を返す(): void
    {
        // arrange
        $instructor = Instructor::find(2);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson(route('manager.course.deadline.clear-selected'), [
            'courses' => [2],
        ]);

        // assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_coursesが空の場合はバリデーションエラーを返す(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson(route('manager.course.deadline.clear-selected'), [
            'courses' => [],
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_coursesが未指定の場合はバリデーションエラーを返す(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson(route('manager.course.deadline.clear-selected'), []);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses']);
    }

    public function test_存在しない講座_i_dの場合はバリデーションエラーを返す(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson(route('manager.course.deadline.clear-selected'), [
            'courses' => [9999],
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_削除済み講座_i_dの場合はバリデーションエラーを返す(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // 講座2を論理削除
        \App\Model\Course::find(2)->delete();

        // act
        $response = $this->postJson(route('manager.course.deadline.clear-selected'), [
            'courses' => [2],
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }

    public function test_整数以外の値が含まれる場合はバリデーションエラーを返す(): void
    {
        // arrange
        $instructor = Instructor::find(1);
        $this->actingAs($instructor, 'instructor');

        // act
        $response = $this->postJson(route('manager.course.deadline.clear-selected'), [
            'courses' => ['invalid'],
        ]);

        // assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['courses.0']);
    }
}
