<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Enums\Course\StatusEnum;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-038, AC-AUTHOR-039, AC-AUTHOR-040 */
    #[DataProvider('capacityAndAttendanceProvider')]
    public function test_講座一覧に定員と受講人数を含む(?int $capacity, int $attendanceCount): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id, 'capacity' => $capacity]);
        Attendance::factory()->count($attendanceCount)->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index'));

        // Assert
        $response->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.course_id', $course->id);
        $response->assertJsonPath('data.0.capacity', $capacity);
        $response->assertJsonPath('data.0.attendances_count', $attendanceCount);
        $response->assertJsonPath('data.0.has_active_students', $attendanceCount > 0);
        $this->assertSame($capacity === null || $attendanceCount < $capacity, $course->hasCapacity());
    }

    /** @return array<string, array{int|null, int}> */
    public static function capacityAndAttendanceProvider(): array
    {
        return [
            '定員ありで受講なし' => [2, 0],
            '定員に空きがある' => [2, 1],
            'ちょうど定員に達している' => [2, 2],
            '定員を超えている' => [2, 3],
            '定員なしで受講なし' => [null, 0],
            '定員なしで受講あり' => [null, 2],
        ];
    }

    /** AC-AUTHOR-041 */
    public function test_受講人数は定員判定と同じく期限切れと修了済みを含み削除済みを除く(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id, 'capacity' => 3]);
        Attendance::factory()->create(['course_id' => $course->id]);
        Attendance::factory()->create(['course_id' => $course->id, 'attendance_deadline' => '2000-01-01']);
        Attendance::factory()->create(['course_id' => $course->id, 'completed_at' => '2000-01-01 12:00:00']);
        $deletedAttendance = Attendance::factory()->create(['course_id' => $course->id]);
        $deletedAttendance->delete();
        Attendance::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index'));

        // Assert
        $response->assertOk()->assertJsonPath('data.0.attendances_count', 3);
        $this->assertSame($course->attendances()->count(), $response->json('data.0.attendances_count'));
        $this->assertFalse($course->hasCapacity());
    }

    /** AC-AUTHOR-038, AC-COMMON-006, AC-COMMON-007, AC-COMMON-008, AC-AUTHZ-015 */
    public function test_検索と分類とページ指定を併用しても既存項目と定員と受講人数を取得できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id, 'content' => '選択分類']);
        $courses = Course::factory()->count(2)->create([
            'instructor_id' => $instructor->id,
            'title' => 'PHP入門',
            'capacity' => 5,
            'status' => StatusEnum::PUBLIC,
        ]);
        foreach ($courses as $course) {
            $course->tags()->attach($tag);
            CourseDeadline::factory()->create(['course_id' => $course->id]);
            Attendance::factory()->create(['course_id' => $course->id]);
        }
        Course::factory()->create(['instructor_id' => $instructor->id, 'title' => 'PHP入門']);
        $unmatchedCourse = Course::factory()->create(['instructor_id' => $instructor->id, 'title' => '別の講座']);
        $unmatchedCourse->tags()->attach($tag);
        $otherCourse = Course::factory()->create(['title' => 'PHP入門']);
        $otherCourse->tags()->attach($tag);
        $this->actingAs($instructor, 'instructor');

        // Act
        $firstPage = $this->getJson(route('instructor.courses.index', [
            'search_word' => 'PHP', 'tag_id' => $tag->id, 'per_page' => 1, 'page' => 1,
        ]));
        $secondPage = $this->getJson(route('instructor.courses.index', [
            'search_word' => 'PHP', 'tag_id' => $tag->id, 'per_page' => 1, 'page' => 2,
        ]));

        // Assert
        $returnedIds = [];
        foreach ([$firstPage, $secondPage] as $page => $response) {
            $response->assertOk()->assertJsonCount(1, 'data');
            $response->assertJsonPath('meta.total', 2);
            $response->assertJsonPath('meta.current_page', $page + 1);
            $response->assertJsonPath('meta.per_page', 1);
            $returnedId = $response->json('data.0.course_id');
            $returnedIds[] = $returnedId;
            $course = $courses->firstWhere('id', $returnedId);
            $this->assertNotNull($course);
            $response->assertJsonPath('data.0.image', $course->image);
            $response->assertJsonPath('data.0.title', $course->title);
            $response->assertJsonPath('data.0.status', StatusEnum::PUBLIC->value);
            $response->assertJsonPath('data.0.has_active_students', true);
            $response->assertJsonPath('data.0.tags.0.tag_id', $tag->id);
            $response->assertJsonPath('data.0.tags.0.content', $tag->content);
            $response->assertJsonPath('data.0.course_deadline.course_deadline_id', $course->courseDeadline->id);
            $response->assertJsonPath('data.0.capacity', 5);
            $response->assertJsonPath('data.0.attendances_count', 1);
        }
        $this->assertEqualsCanonicalizing($courses->modelKeys(), $returnedIds);
    }

    /** AC-AUTHOR-038, AC-COMMON-011 */
    public function test_該当講座がないときは空の一覧とページ情報を返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        Course::factory()->create(['instructor_id' => $instructor->id, 'title' => 'PHP入門']);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index', ['search_word' => '該当なし']));

        // Assert
        $response->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.total', 0);
    }

    /** AC-AUTHOR-038 */
    public function test_講座が増えても受講人数を取得する問い合わせ回数は増えない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        DB::enableQueryLog();
        try {
            DB::flushQueryLog();
            $firstResponse = $this->getJson(route('instructor.courses.index'));
            $firstQueryCount = count(DB::getQueryLog());
            $additionalCourses = Course::factory()->count(3)->create(['instructor_id' => $instructor->id]);
            foreach ($additionalCourses as $additionalCourse) {
                Attendance::factory()->create(['course_id' => $additionalCourse->id]);
            }
            DB::flushQueryLog();
            $secondResponse = $this->getJson(route('instructor.courses.index'));
            $secondQueryCount = count(DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }

        // Assert
        $firstResponse->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.attendances_count', 1);
        $secondResponse->assertOk()->assertJsonCount(4, 'data');
        foreach ($secondResponse->json('data') as $returnedCourse) {
            $this->assertSame(1, $returnedCourse['attendances_count']);
        }
        $this->assertSame($firstQueryCount, $secondQueryCount);
    }

    /** AC-AUTHOR-038, AC-AUTHOR-039, AC-AUTHOR-040 */
    public function test_講座一覧の定員と受講人数の型を画面側が正しく参照できる(): void
    {
        // Arrange
        $path = tempnam(sys_get_temp_dir(), 'instructor-course-schema-');
        $this->assertNotFalse($path);

        // Act
        try {
            $exitCode = Artisan::call('scramble:export', ['--routes' => 'instructor.courses.index', '--path' => $path]);
            $schema = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } finally {
            unlink($path);
        }

        // Assert
        $this->assertSame(0, $exitCode);
        $courseSchema = $schema['components']['schemas']['CourseIndexResource'];
        $this->assertContains('capacity', $courseSchema['required']);
        $this->assertContains('attendances_count', $courseSchema['required']);
        $this->assertEqualsCanonicalizing(['integer', 'null'], $courseSchema['properties']['capacity']['type']);
        $this->assertSame('integer', $courseSchema['properties']['attendances_count']['type']);
    }

    public function test_講座一覧取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        Course::factory()->count(2)->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_パラメータ指定_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $manager->id]);
        $taggedCourse = Course::factory()->create(['instructor_id' => $manager->id]);
        $taggedCourse->tags()->attach($tag->id);
        Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index', ['per_page' => 5, 'tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_タイトル検索_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        Course::factory()->create(['instructor_id' => $manager->id, 'title' => 'PHP入門講座']);
        Course::factory()->create(['instructor_id' => $manager->id, 'title' => 'JavaScript入門講座']);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index', ['search_word' => 'PHP']));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_タグ名検索_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $manager->id, 'content' => 'バックエンド入門編']);
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $course->tags()->attach($tag->id);
        Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index', ['search_word' => 'バックエンド入門編']));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }
}
