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

    /** AC-PROG-005 */
    public function test_受講生は受講状況を未着手から受講中に更新できる(): void
    {
        // Arrange
        $lessonAttendance = $this->createLessonAttendanceForActingStudent();

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => LessonAttendanceStatusEnum::IN_ATTENDANCE]
        );

        // Assert — 受講中にしただけでは完了日時は記録されない
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'id' => $lessonAttendance->id,
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => null,
        ]);
    }

    /** AC-PROG-007 */
    public function test_受講済みから受講中に戻しても完了日時は消えない(): void
    {
        // Arrange — 過去に一度受講済みになっているレッスン
        $completedAt = CarbonImmutable::parse('2026-07-01 09:00:00');
        $lessonAttendance = $this->createLessonAttendanceForActingStudent([
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => $completedAt,
        ]);

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

    /** AC-PROG-008, AC-AUTHZ-006 */
    public function test_他の受講生の受講状況は更新できない(): void
    {
        // Arrange — 別の受講生のレッスン受講状況にアクセスする
        $lessonAttendance = $this->createLessonAttendanceForActingStudent();
        $otherStudent = Student::factory()->create();
        $this->actingAs($otherStudent);

        // Act
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => LessonAttendanceStatusEnum::IN_ATTENDANCE]
        );

        // Assert
        $response->assertStatus(403);
    }

    public function test_受講状況idが数字でないか状態が未知の値なら入力エラーになる(): void
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

    /** AC-PROG-006 */
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

    /** AC-PROG-008, AC-ENROLL-012 */
    #[DataProvider('blockedUpdateProvider')]
    public function test_期限切れまたは公開されていない講座では受講状況を更新できない(
        CourseStatusEnum $courseStatus,
        ?string $deadline,
    ): void {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-02 00:00:00'));
        $lessonAttendance = $this->createLessonAttendanceForActingStudent([
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => null,
        ]);
        $lessonAttendance->attendance->update(['attendance_deadline' => $deadline]);
        $lessonAttendance->attendance->course->update(['status' => $courseStatus]);

        // Act — 初めて完了にしようとする
        $response = $this->patchJson(
            route('student.lesson-attendances.patch-status', ['lesson_attendance_id' => $lessonAttendance->id]),
            ['status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE]
        );

        // Assert — 段階も完了日時も変わらない
        $response->assertForbidden();
        $this->assertDatabaseHas('lesson_attendances', [
            'id' => $lessonAttendance->id,
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => null,
        ]);
    }

    /** @return array<string, array{CourseStatusEnum, ?string}> */
    public static function blockedUpdateProvider(): array
    {
        return [
            '期限翌日の午前零時' => [CourseStatusEnum::PUBLIC, '2026-08-01'],
            '期限なしで非公開' => [CourseStatusEnum::PRIVATE, null],
            '期限なしで下書き' => [CourseStatusEnum::DRAFT, null],
            '期限切れかつ非公開' => [CourseStatusEnum::PRIVATE, '2026-08-01'],
        ];
    }

    /** AC-PROG-006, AC-PROG-008, AC-ENROLL-012, AC-ENROLL-013 */
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
