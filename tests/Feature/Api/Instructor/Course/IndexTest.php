<?php

namespace Tests\Feature\Api\Instructor\Course;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $response->assertOk()
            ->assertJsonPath('data.0.capacity', $capacity)
            ->assertJsonPath('data.0.attendances_count', $attendanceCount);
    }

    /** @return array<string, array{int|null, int}> */
    public static function capacityAndAttendanceProvider(): array
    {
        return [
            '定員ありで受講あり' => [2, 1],
            '定員ありで受講なし' => [2, 0],
            '定員なしで受講あり' => [null, 1],
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
        Attendance::factory()->create(['course_id' => $course->id])->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index'));

        // Assert
        $response->assertOk()->assertJsonPath('data.0.attendances_count', 3);
        $this->assertFalse($course->hasCapacity());
    }

    public function test_講座が増えても問い合わせ回数は変わらない(): void
    {
        // Arrange
        $oneCourseInstructor = Instructor::factory()->create(['type' => 'instructor']);
        Course::factory()->create(['instructor_id' => $oneCourseInstructor->id]);
        $manyCoursesInstructor = Instructor::factory()->create(['type' => 'instructor']);
        Course::factory()->count(4)->create(['instructor_id' => $manyCoursesInstructor->id]);

        // Act
        $oneCourseQueryCount = $this->countQueriesOnIndex($oneCourseInstructor);
        $manyCoursesQueryCount = $this->countQueriesOnIndex($manyCoursesInstructor);

        // Assert
        $this->assertSame(
            $oneCourseQueryCount,
            $manyCoursesQueryCount,
            '講座数に比例して問い合わせが増えている（N+1が発生している）'
        );
    }

    public function test_自分の講座の一覧を取得できる(): void
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

    /** AC-COMMON-006 */
    public function test_per_pageとpageで講座をページングできる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        Course::factory()->count(3)->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $firstPage = $this->getJson(route('instructor.courses.index', ['per_page' => 2, 'page' => 1]));
        $secondPage = $this->getJson(route('instructor.courses.index', ['per_page' => 2, 'page' => 2]));

        // Assert
        $firstPage->assertStatus(200);
        $firstPage->assertJsonCount(2, 'data');
        $secondPage->assertStatus(200);
        $secondPage->assertJsonCount(1, 'data');
    }

    public function test_タグで講座を絞り込める(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $tag = Tag::factory()->create(['instructor_id' => $manager->id]);
        $taggedCourse = Course::factory()->create(['instructor_id' => $manager->id]);
        $taggedCourse->tags()->attach($tag->id);
        Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.index', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_講座名で検索できる(): void
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

    public function test_タグ名で検索できる(): void
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

    /**
     * 講座一覧取得で発行された問い合わせ回数を数える
     */
    private function countQueriesOnIndex(Instructor $instructor): int
    {
        $this->actingAs($instructor, 'instructor');

        return $this->countQueries(fn () => $this->getJson(route('instructor.courses.index'))->assertOk());
    }
}
