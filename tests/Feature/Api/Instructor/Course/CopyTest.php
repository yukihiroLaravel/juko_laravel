<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Course\DeadlineTypeEnum;
use App\Enums\Course\StatusEnum as CourseStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\ManageInstructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Override;
use Tests\TestCase;

class CopyTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /** AC-AUTHOR-033 */
    public function test_講座を複製すると講座とチャプターとレッスンが下書きで複製される(): void
    {
        // Arrange — 公開中のチャプターとレッスンを持つ講座
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id, 'title' => '元の講座']);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.copy', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $copiedCourse = Course::where('title', '元の講座 - コピー')->sole();
        $this->assertSame(CourseStatusEnum::DRAFT, $copiedCourse->status);
        $copiedChapter = Chapter::where('course_id', $copiedCourse->id)->sole();
        $this->assertSame($chapter->title, $copiedChapter->title);
        $this->assertSame(ChapterStatusEnum::DRAFT, $copiedChapter->status);
        $copiedLesson = Lesson::where('chapter_id', $copiedChapter->id)->sole();
        $this->assertSame($lesson->title, $copiedLesson->title);
        $this->assertSame(LessonStatusEnum::DRAFT, $copiedLesson->status);
        $response->assertJsonPath('course.course_id', $copiedCourse->id);
        $response->assertJsonPath('course.chapters.0.lessons.0.lesson_id', $copiedLesson->id);
    }

    /** AC-AUTHOR-034 */
    public function test_受講期限と定員は引き継がず期限なしと定員なしで複製される(): void
    {
        // Arrange — 受講期限と定員を設定した講座
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'deadline_type' => DeadlineTypeEnum::RELATIVE_DAYS->value,
            'capacity' => 10,
        ]);
        CourseDeadline::factory()->create(['course_id' => $course->id, 'relative_days' => 30]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.copy', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $copiedCourseId = $response->json('course.course_id');
        $this->assertDatabaseHas('courses', [
            'id' => $copiedCourseId,
            'deadline_type' => DeadlineTypeEnum::NONE->value,
            'capacity' => null,
        ]);
        $this->assertDatabaseMissing('course_deadlines', ['course_id' => $copiedCourseId]);
    }

    /** AC-AUTHOR-035 */
    public function test_マネージャーが配下の講師の講座を複製すると担当講師とタグは複製元のままになる(): void
    {
        // Arrange — 配下の講師が作成したタグを付けた、配下の講師の講座
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $tags = Tag::factory()->count(2)->create(['instructor_id' => $subordinate->id]);
        $course->tags()->attach($tags->pluck('id'));
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.copy', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $copiedCourse = Course::findOrFail($response->json('course.course_id'));
        $this->assertSame($subordinate->id, $copiedCourse->instructor_id);
        $this->assertEqualsCanonicalizing(
            $tags->pluck('id')->all(),
            $copiedCourse->tags()->pluck('tags.id')->all()
        );
    }

    /** AC-AUTHOR-035 */
    public function test_タグのない講座も複製できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.copy', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseMissing('course_tag', ['course_id' => $response->json('course.course_id')]);
    }

    /** AC-AUTHOR-036 */
    public function test_サムネイル画像は別のファイルとして複製される(): void
    {
        // Arrange — サムネイル画像の実体がある講座
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id, 'image' => 'course/original.png']);
        Storage::disk('public')->put('course/original.png', 'image');
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.copy', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $copiedImage = Course::findOrFail($response->json('course.course_id'))->image;
        $this->assertNotSame('course/original.png', $copiedImage);
        Storage::disk('public')->assertExists(['course/original.png', $copiedImage]);
    }

    /** AC-AUTHOR-036 */
    public function test_サムネイル画像の実体がない講座も複製できる(): void
    {
        // Arrange — サムネイル画像の実体がない講座
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id, 'image' => 'course/missing.png']);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.copy', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $copiedImage = Course::findOrFail($response->json('course.course_id'))->image;
        $this->assertNotSame('course/missing.png', $copiedImage);
        Storage::disk('public')->assertMissing($copiedImage);
    }

    /** AC-AUTHOR-037 */
    public function test_配下にない講師の講座は複製できない(): void
    {
        // Arrange — マネージャーの配下にない講師の講座
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.copy', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseCount('courses', 1);
    }
}
