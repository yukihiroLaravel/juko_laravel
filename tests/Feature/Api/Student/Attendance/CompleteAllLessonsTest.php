<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Course\StatusEnum as CourseStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CompleteAllLessonsTest extends TestCase
{
    use RefreshDatabase;

    /** AC-PROG-009 */
    #[DataProvider('notPublicStatusProvider')]
    public function test_公開されていないレッスンは完了にならない(string $lessonStatus): void
    {
        // Arrange — 公開中のチャプターに公開レッスンと公開されていないレッスンがある
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
        $openLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $closedLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => $lessonStatus,
        ]);
        foreach ([$openLesson, $closedLesson] as $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
            ]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => $attendance->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $openLesson->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
        ]);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $closedLesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
    }

    /** @return array<string, array{string}> */
    public static function notPublicStatusProvider(): array
    {
        return [
            '下書き' => ['draft'],
            '非公開' => ['private'],
        ];
    }

    /** AC-AUTHZ-013 */
    #[DataProvider('notPublicStatusProvider')]
    public function test_公開されていないチャプターは完了にできない(string $chapterStatus): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => $chapterStatus,
        ]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => $attendance->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
    }

    /** AC-AUTHZ-007 */
    public function test_公開されていない講座は完了にできない(): void
    {
        // Arrange — 受講中の講座が非公開になっている
        $student = Student::factory()->create();
        $course = Course::factory()->create(['status' => CourseStatusEnum::PRIVATE->value]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
        $lesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => $attendance->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert — 受講生に見えない講座は完了操作を受け付けない
        $response->assertStatus(403);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
            'completed_at' => null,
        ]);
    }

    /** AC-AUTHZ-006 */
    public function test_ほかの生徒の受講はまとめて完了にできない(): void
    {
        // Arrange
        $owner = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $owner->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $unauthorizedStudent = Student::factory()->create();
        $this->actingAs($unauthorizedStudent);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => $attendance->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    /** AC-AUTHZ-013 */
    public function test_受講している講座に属さないチャプターは完了にできない(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $otherCourse = Course::factory()->create();
        $otherChapter = Chapter::factory()->create(['course_id' => $otherCourse->id]);
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => $attendance->id,
            'chapter_id' => $otherChapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_受講とチャプターの指定が整数でないときは入力エラーになる(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => 'aaa',
            'chapter_id' => 'bbb',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'attendance_id',
            'chapter_id',
        ]);
    }

    /** AC-PROG-011 */
    public function test_未着手のレッスンにだけ完了日時が記録される(): void
    {
        // Arrange — 未着手のレッスンと、過去に完了済みのレッスン
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-01 10:00:00'));
        $completedAt = CarbonImmutable::parse('2026-07-01 09:00:00');
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $newLesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $completedLesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $newLesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $completedLesson->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => $completedAt,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => $attendance->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert — 未着手だったレッスンには現在時刻が記録され、既に完了していたレッスンの日時は変わらない
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $newLesson->id,
            'completed_at' => CarbonImmutable::now(),
        ]);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $completedLesson->id,
            'completed_at' => $completedAt,
        ]);
    }

    /** AC-PROG-020 */
    public function test_受講期限を過ぎた講座ではチャプター単位でまとめて完了にできない(): void
    {
        // Arrange — 受講期限の翌日になった直後
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-02-01 00:00:00'));
        $completedAt = CarbonImmutable::parse('2026-01-01 10:00:00');
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => '2026-01-31',
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $unfinishedLesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $relearningLesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $unfinishedLesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $relearningLesson->id,
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => $completedAt,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => $attendance->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $unfinishedLesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
            'completed_at' => null,
        ]);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $relearningLesson->id,
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => $completedAt,
        ]);
    }

    /** AC-ENROLL-012, AC-ENROLL-013 */
    #[DataProvider('withinDeadlineProvider')]
    public function test_受講期限内の講座ではチャプター単位でまとめて完了にできる(string $now, ?string $deadline): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse($now));
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => $deadline,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-lessons', [
            'attendance_id' => $attendance->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
        ]);
    }

    /** @return array<string, array{string, string|null}> */
    public static function withinDeadlineProvider(): array
    {
        return [
            '受講期限当日の終了時刻' => ['2026-01-31 23:59:59', '2026-01-31'],
            '受講期限の前日' => ['2026-01-30 12:00:00', '2026-01-31'],
            '受講期限なし' => ['2026-01-31 23:59:59', null],
        ];
    }
}
