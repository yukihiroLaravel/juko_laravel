<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講期限なし_講座登録_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.course.store'), [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => $tag->id,
            'deadline_type' => 'none',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'title' => 'テスト講座',
        ]);
        $course = Course::where('title', 'テスト講座')->first();
        $this->assertDatabaseHas('course_tag', [
            'course_id' => $course->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseMissing('course_deadlines', [
            'course_id' => $course->id,
        ]);
    }

    public function test_固定受講期限あり_講座登録_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.course.store'), [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => $tag->id,
            'deadline_type' => 'fixed_date',
            'fixed_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'title' => 'テスト講座',
        ]);
        $course = Course::where('title', 'テスト講座')->first();
        $this->assertDatabaseHas('course_tag', [
            'course_id' => $course->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course->id,
            'fixed_date' => now()->addDays(30)->format('Y-m-d 00:00:00'),
        ]);
    }

    public function test_相対受講期限あり_講座登録_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.course.store'), [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => $tag->id,
            'deadline_type' => 'relative_days',
            'relative_days' => 30,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'title' => 'テスト講座',
        ]);
        $course = Course::where('title', 'テスト講座')->first();
        $this->assertDatabaseHas('course_tag', [
            'course_id' => $course->id,
            'tag_id' => $tag->id,
        ]);
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course->id,
            'relative_days' => 30,
        ]);
    }

    public function test_無効なタグの指定_失敗(): void
    {
        // Arrange — 別の講師のタグを指定
        $instructor = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create();
        $otherTag = Tag::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.course.store'), [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => $otherTag->id,
            'deadline_type' => 'none',
        ]);

        // Assert
        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Not Found Tag.',
        ]);
    }

    public function test_バリデーションエラー_失敗(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->post(route('instructor.course.store'), [
            'title' => '',
            'image' => null,
            'tag_id' => '',
            'deadline_type' => '',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title' => 'The title field is required.',
            'image' => 'The image field is required.',
            'tag_id' => 'The tag id field is required.',
            'deadline_type' => 'The deadline type field is required.',
        ]);
    }
}
