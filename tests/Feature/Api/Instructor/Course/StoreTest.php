<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Enums\Course\StatusEnum as CourseStatusEnum;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // 講座画像は既定のディスクに保存する
        Storage::fake();
    }

    /** AC-AUTHOR-001 */
    public function test_受講期限なし_講座登録_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.courses.store'), [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => $tag->id,
            'deadline_type' => 'none',
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'title' => 'テスト講座',
            'status' => CourseStatusEnum::DRAFT->value,
            'capacity' => null,
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
        $response = $this->post(route('instructor.courses.store'), [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => $tag->id,
            'deadline_type' => 'fixed_date',
            'fixed_date' => now()->addDays(30)->format('Y-m-d'),
        ]);

        // Assert
        $response->assertStatus(200);
        $course = Course::where('title', 'テスト講座')->first();
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
        $response = $this->post(route('instructor.courses.store'), [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => $tag->id,
            'deadline_type' => 'relative_days',
            'relative_days' => 30,
        ]);

        // Assert
        $response->assertStatus(200);
        $course = Course::where('title', 'テスト講座')->first();
        $this->assertDatabaseHas('course_deadlines', [
            'course_id' => $course->id,
            'relative_days' => 30,
        ]);
    }

    /** AC-AUTHOR-003 */
    public function test_他の講師のタグは指定できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $otherTag = Tag::factory()->create(['instructor_id' => $otherInstructor->id]);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.courses.store'), [
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

    /** AC-AUTHOR-006 */
    public function test_定員あり_講座登録_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.courses.store'), [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => $tag->id,
            'deadline_type' => 'none',
            'capacity' => 30,
        ]);

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('courses', [
            'title' => 'テスト講座',
            'capacity' => 30,
        ]);
    }

    /** AC-AUTHOR-006 */
    #[DataProvider('範囲外の定員')]
    public function test_定員が1人から100人の範囲外だと登録できない(int $capacity): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');
        $file = UploadedFile::fake()->image('test.jpg');

        // Act
        $response = $this->post(route('instructor.courses.store'), [
            'title' => 'テスト講座',
            'image' => $file,
            'tag_id' => $tag->id,
            'deadline_type' => 'none',
            'capacity' => $capacity,
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['capacity']);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function 範囲外の定員(): array
    {
        return [
            '0人' => [0],
            '101人' => [101],
        ];
    }

    /** AC-AUTHOR-002 */
    public function test_必須項目が空だと登録できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->post(route('instructor.courses.store'), [
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
