<?php

namespace Tests\Feature\Api\Student\LessonAttendance;

use App\Enums\Course\StatusEnum as CourseStatusEnum;
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

class PatchStatusTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_レッスン受講状態を更新_成功(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lessonAttendance = LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => 'before_attendance']
        );

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'id' => $lessonAttendance->id,
            'status' => 'before_attendance',
        ]);
    }

    public function test_レッスンを受講済みにすると完了日時が記録される(): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-01 10:00:00'));
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lessonAttendance = LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE]
        );

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'id' => $lessonAttendance->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now(),
        ]);
    }

    public function test_受講済みから受講中に戻しても完了日時は消えない(): void
    {
        // Arrange — 過去に一度受講済みになっているレッスン
        $completedAt = CarbonImmutable::parse('2026-07-01 09:00:00');
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lessonAttendance = LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => $completedAt,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => LessonAttendanceStatusEnum::IN_ATTENDANCE]
        );

        // Assert — 過去に受講済みになった事実は残る
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'id' => $lessonAttendance->id,
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => $completedAt,
        ]);
    }

    public function test_レッスン受講状態を更新_他の生徒の受講状態を更新_失敗(): void
    {
        // Arrange — 別の生徒のLessonAttendanceにアクセス
        $owner = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $owner->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lessonAttendance = LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
        ]);
        $otherStudent = Student::factory()->create();
        $this->actingAs($otherStudent);

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => 'before_attendance']
        );

        // Assert
        $response->assertStatus(403);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => 'abc']),
            ['status' => 'aaa']
        );

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'lesson_attendance_id',
            'status',
        ]);
    }

    public function test_受講済みにするまでは完了日時が記録されない(): void
    {
        // Arrange
        $lessonAttendance = $this->createLessonAttendanceForActingStudent();

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => LessonAttendanceStatusEnum::IN_ATTENDANCE]
        );

        // Assert
        $response->assertStatus(200);
        $this->assertNull($lessonAttendance->fresh()->completed_at);
    }

    public function test_学び直して再び受講済みにしても最初の完了日時が保たれる(): void
    {
        // Arrange — 過去に受講済みになったあと受講中に戻したレッスン
        $firstCompletedAt = CarbonImmutable::parse('2026-01-01 10:00:00');
        $lessonAttendance = $this->createLessonAttendanceForActingStudent([
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => $firstCompletedAt,
        ]);

        // Act — 学び直して再び完了にする
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE]
        );

        // Assert — 最初に終えた日時は上書きされない
        $response->assertStatus(200);
        $this->assertTrue($firstCompletedAt->equalTo($lessonAttendance->fresh()->completed_at));
    }

    /** AC-PROG-008 */
    #[DataProvider('blockedUpdateProvider')]
    public function test_期限切れまたは公開されていない講座では受講状況を変更できない(
        CourseStatusEnum $courseStatus,
        ?string $deadline,
        LessonAttendanceStatusEnum $initialStatus,
        ?string $completedAt,
        LessonAttendanceStatusEnum $requestedStatus,
    ): void {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-02 00:00:00'));
        $lessonAttendance = $this->createLessonAttendanceForActingStudent([
            'status' => $initialStatus,
            'completed_at' => $completedAt,
        ]);
        $lessonAttendance->attendance->update(['attendance_deadline' => $deadline]);
        $lessonAttendance->attendance->course->update(['status' => $courseStatus]);

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => $requestedStatus]
        );

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseHas('lesson_attendances', [
            'id' => $lessonAttendance->id,
            'status' => $initialStatus,
            'completed_at' => $completedAt,
        ]);
    }

    /**
     * @return iterable<string, array{CourseStatusEnum, ?string, LessonAttendanceStatusEnum, ?string,
     *     LessonAttendanceStatusEnum}>
     */
    public static function blockedUpdateProvider(): iterable
    {
        $conditions = [
            '期限翌日の午前零時' => [CourseStatusEnum::PUBLIC, '2026-08-01'],
            '期限なしで非公開' => [CourseStatusEnum::PRIVATE, null],
            '期限なしで下書き' => [CourseStatusEnum::DRAFT, null],
            '期限切れかつ非公開' => [CourseStatusEnum::PRIVATE, '2026-08-01'],
        ];
        $transitions = [
            '未着手から受講中' => [
                LessonAttendanceStatusEnum::BEFORE_ATTENDANCE, null,
                LessonAttendanceStatusEnum::IN_ATTENDANCE,
            ],
            '初めて完了' => [
                LessonAttendanceStatusEnum::IN_ATTENDANCE, null,
                LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            ],
            '完了から未着手' => [
                LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE, '2026-07-01 09:00:00',
                LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
            ],
        ];

        foreach ($conditions as $condition => $values) {
            foreach ($transitions as $transition => $statuses) {
                yield $condition.'・'.$transition => [...$values, ...$statuses];
            }
        }
    }

    /** AC-PROG-008, AC-ENROLL-012 */
    #[DataProvider('allowedUpdateProvider')]
    public function test_公開講座で受講期限内または期限なしなら受講状況を更新できる(?string $deadline): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-01 23:59:59'));
        $lessonAttendance = $this->createLessonAttendanceForActingStudent([
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => null,
        ]);
        $lessonAttendance->attendance->update(['attendance_deadline' => $deadline]);
        $lessonAttendance->attendance->course->update(['status' => CourseStatusEnum::PUBLIC]);

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE]
        );

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('lesson_attendances', [
            'id' => $lessonAttendance->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now(),
        ]);
    }

    /** @return array<string, array{?string}> */
    public static function allowedUpdateProvider(): array
    {
        return [
            '期限当日の23時59分59秒' => ['2026-08-01'],
            '期限より前' => ['2026-08-02'],
            '期限なし' => [null],
        ];
    }

    /**
     * ログイン中の受講生に紐づくレッスン受講状況を用意する
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createLessonAttendanceForActingStudent(array $attributes = []): LessonAttendance
    {
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($student);

        return LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            ...$attributes,
        ]);
    }
}
