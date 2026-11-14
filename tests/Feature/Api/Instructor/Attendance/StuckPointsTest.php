<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StuckPointsTest extends TestCase
{
    use RefreshDatabase;

    /** AC-ANALYTICS-018 */
    public function test_受講期限を過ぎていない受講生が2人未満の場合は空で返す(): void
    {
        // Arrange — 受講は2人いるが、期限を過ぎていないのは1人だけ
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['chapter_id' => $chapter->id]);
        Attendance::factory()->create([
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::tomorrow(),
        ]);
        Attendance::factory()->create([
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::yesterday(),
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    /** AC-ANALYTICS-019 */
    public function test_完了したレッスンがない場合は最初の公開レッスンを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
            'title' => 'チャプター1',
        ]);
        $firstLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 1,
            'title' => 'レッスン1',
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);
        Attendance::factory()->count(2)->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'chapter_id' => $chapter->id,
            'title' => 'チャプター1',
        ]);
        $response->assertJsonFragment([
            'lesson_id' => $firstLesson->id,
            'title' => 'レッスン1',
        ]);
    }

    /** AC-ANALYTICS-021 */
    public function test_完了した受講生が最も多いレッスンが最終レッスン以外の場合は次のレッスンを返す(): void
    {
        // Arrange — 受講生2人がともに2つ目のレッスンまで完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'title' => 'チャプター1',
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 1,
        ]);
        $targetLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);
        $nextLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 3,
            'title' => '次のレッスン',
        ]);
        $attendances = Attendance::factory()->count(2)->create(['course_id' => $course->id]);
        foreach ($attendances as $attendance) {
            LessonAttendance::factory()->completed()->create([
                'lesson_id' => $targetLesson->id,
                'attendance_id' => $attendance->id,
            ]);
        }
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'chapter_id' => $chapter->id,
            'title' => 'チャプター1',
        ]);
        $response->assertJsonFragment([
            'lesson_id' => $nextLesson->id,
            'title' => '次のレッスン',
        ]);
    }

    /**
     * 公開されているものがない講座（チャプターの公開状態, レッスンの公開状態）
     *
     * @return array<string, array{string, string}>
     */
    public static function unpublishedContents(): array
    {
        return [
            '公開チャプターがない' => ['private', 'public'],
            '公開チャプターに公開レッスンがない' => ['public', 'private'],
        ];
    }

    /** AC-ANALYTICS-017 */
    #[DataProvider('unpublishedContents')]
    public function test_公開されたチャプターかレッスンがない場合は空で返す(string $chapterStatus, string $lessonStatus): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => $chapterStatus]);
        Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => $lessonStatus]);
        Attendance::factory()->count(2)->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    /** AC-ANALYTICS-019 */
    public function test_完了した受講生が最も多いレッスンでも2人に満たない場合は最初の公開レッスンを返す(): void
    {
        // Arrange — 受講生2人のうち、2つ目のレッスンを完了したのは1人だけ
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'title' => 'チャプター1',
        ]);
        $firstLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 1,
            'title' => 'レッスン1',
        ]);
        $secondLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 3,
        ]);
        $attendances = Attendance::factory()->count(2)->create(['course_id' => $course->id]);
        LessonAttendance::factory()->completed()->create([
            'lesson_id' => $secondLesson->id,
            'attendance_id' => $attendances[0]->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'chapter_id' => $chapter->id,
            'title' => 'チャプター1',
        ]);
        $response->assertJsonFragment([
            'lesson_id' => $firstLesson->id,
            'title' => 'レッスン1',
        ]);
    }

    /** AC-ANALYTICS-020 */
    public function test_完了した受講生が最も多いレッスンが最終レッスンだけの場合は空で返す(): void
    {
        // Arrange — 受講生2人がともに最終レッスンを完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 1,
        ]);
        $lastLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);
        $attendances = Attendance::factory()->count(2)->create(['course_id' => $course->id]);
        foreach ($attendances as $attendance) {
            LessonAttendance::factory()->completed()->create([
                'lesson_id' => $lastLesson->id,
                'attendance_id' => $attendance->id,
            ]);
        }
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    /** AC-ANALYTICS-021 */
    public function test_完了した受講生が最も多いレッスンがチャプターの最後の場合は次のチャプターの最初のレッスンを返す(): void
    {
        // Arrange — 受講生2人がともにチャプター1の最後のレッスンまで完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
        ]);
        $chapter2 = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 2,
            'title' => 'チャプター2',
        ]);
        $chapter1LastLesson = Lesson::factory()->create([
            'chapter_id' => $chapter1->id,
            'order' => 1,
        ]);
        $chapter2FirstLesson = Lesson::factory()->create([
            'chapter_id' => $chapter2->id,
            'order' => 1,
            'title' => 'チャプター2の最初のレッスン',
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter2->id,
            'order' => 2,
        ]);
        $attendances = Attendance::factory()->count(2)->create(['course_id' => $course->id]);
        foreach ($attendances as $attendance) {
            LessonAttendance::factory()->completed()->create([
                'lesson_id' => $chapter1LastLesson->id,
                'attendance_id' => $attendance->id,
            ]);
        }
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'chapter_id' => $chapter2->id,
            'title' => 'チャプター2',
        ]);
        $response->assertJsonFragment([
            'lesson_id' => $chapter2FirstLesson->id,
            'title' => 'チャプター2の最初のレッスン',
        ]);
    }

    /** AC-AUTHZ-001 */
    public function test_他の講師の講座のつまずき箇所は取得できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    /**
     * @return array<string, array{int|string}>
     */
    public static function invalidCourseIds(): array
    {
        return [
            '数値でない値' => ['aaa'],
            '存在しない講座' => [99999],
        ];
    }

    #[DataProvider('invalidCourseIds')]
    public function test_講座の指定が不正な場合はつまずき箇所を取得できない(int|string $courseId): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $courseId,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }

    public function test_削除済みの講座のつまずき箇所は取得できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $course->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.stuck-points', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }
}
