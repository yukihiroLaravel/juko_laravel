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
use PHPUnit\Framework\Attributes\DataProvider;
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
        $this->assertSame($chapter->only(['title', 'order']), $copiedChapter->only(['title', 'order']));
        $this->assertSame(ChapterStatusEnum::DRAFT, $copiedChapter->status);
        $copiedLesson = Lesson::where('chapter_id', $copiedChapter->id)->sole();
        $this->assertSame(
            $lesson->only(['title', 'url', 'remarks', 'order']),
            $copiedLesson->only(['title', 'url', 'remarks', 'order'])
        );
        $this->assertSame(LessonStatusEnum::DRAFT, $copiedLesson->status);
        $response->assertJsonPath('course.course_id', $copiedCourse->id);
        $response->assertJsonPath('course.chapters.0.lessons.0.lesson_id', $copiedLesson->id);
    }

    /** AC-AUTHOR-033 */
    #[DataProvider('titleLengthProvider')]
    public function test_複製した講座の講座名は50文字に収まる(string $title, string $expectedTitle): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id, 'title' => $title]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.copy', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('course.title', $expectedTitle);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function titleLengthProvider(): array
    {
        return [
            '44文字はそのまま50文字になる' => [str_repeat('あ', 44), str_repeat('あ', 44).' - コピー'],
            '45文字は末尾の1文字を切り詰める' => [str_repeat('あ', 44).'い', str_repeat('あ', 44).' - コピー'],
        ];
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

    /** AC-AUTHOR-035 */
    public function test_担当講師以外が作成したタグが付いた講座は複製できない(): void
    {
        // Arrange — 他の講師が作成したタグが付いた講座
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->tags()->attach(Tag::factory()->create());
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.copy', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(404);
        $this->assertDatabaseCount('courses', 1);
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
    #[DataProvider('instructorTypeProvider')]
    public function test_担当でも配下でもない講師の講座は複製できない(string $type): void
    {
        // Arrange — 操作者の担当でも配下でもない講師の講座
        $operator = Instructor::factory()->create(['type' => $type]);
        $course = Course::factory()->create();
        $this->actingAs($operator, 'instructor');

        // Act
        $response = $this->postJson(route('instructor.courses.copy', ['course_id' => $course->id]));

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseCount('courses', 1);
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
}
