<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
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

class ProgressTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /** AC-PROG-013 */
    public function test_完了日時がなければ表示用ステータスが完了でも受講済みにならない(): void
    {
        // Arrange — 表示用ステータスは完了だが、完了日時が記録されていない状態
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
        $lesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => null,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert — 完了日時が判定の正なので、受講済みとして数えない
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_completed_lessons', 0);
        $response->assertJsonPath('data.number_of_completed_chapters', 0);
        $response->assertJsonPath('data.continue_from.lesson_id', $lesson->id);
    }

    /** AC-PROG-012 */
    public function test_公開レッスンがないチャプターは進捗の件数に含まれない(): void
    {
        // Arrange — 公開レッスンを持つチャプターと、下書きレッスンしか持たないチャプター
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapterWithOpenLesson = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
        $chapterWithDraftLessonOnly = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
        $openLesson = Lesson::factory()->create([
            'chapter_id' => $chapterWithOpenLesson->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapterWithDraftLessonOnly->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);
        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $openLesson->id,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert — 受講しようがないチャプターは分母に入らず、すべて受講済みになる
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_total_chapters', 1);
        $response->assertJsonPath('data.number_of_completed_chapters', 1);
        $response->assertJsonPath('data.continue_from', null);
    }

    /** AC-PROG-012 */
    public function test_公開されていないチャプターとレッスンは進捗の件数に含まれない(): void
    {
        // Arrange — 公開チャプター（公開レッスン1・下書きレッスン1）と非公開チャプター（レッスン1）
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $openChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
        $closedChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PRIVATE->value,
        ]);
        $openLesson = Lesson::factory()->create([
            'chapter_id' => $openChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $draftLesson = Lesson::factory()->create([
            'chapter_id' => $openChapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);
        $closedChapterLesson = Lesson::factory()->create([
            'chapter_id' => $closedChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        // 完了済みでも数えないことと、未着手のままでも完了を妨げないことを、両方確かめる
        foreach ([$openLesson, $draftLesson] as $lesson) {
            LessonAttendance::factory()->completed()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
            ]);
        }
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $closedChapterLesson->id,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert — 公開チャプター1つ・公開レッスン1つだけが件数に入る
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_total_chapters', 1);
        $response->assertJsonPath('data.number_of_completed_chapters', 1);
        $response->assertJsonPath('data.number_of_total_lessons', 1);
        $response->assertJsonPath('data.number_of_completed_lessons', 1);
        $response->assertJsonPath('data.continue_from', null);
    }

    /** AC-AUTHZ-006 */
    public function test_他の受講生の進捗は取得できない(): void
    {
        // Arrange — 別の生徒のAttendanceにアクセス
        $owner = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $owner->id,
            'course_id' => $course->id,
        ]);
        $otherStudent = Student::factory()->create();
        $this->actingAs($otherStudent);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_受講idが数字でない場合は入力エラーになる(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => 'abc']));

        // Assert
        $response->assertStatus(422);
    }

    /** AC-AUTHZ-008, AC-ENROLL-012 */
    #[DataProvider('deadlineProvider')]
    public function test_受講期限の当日までは進捗を取得でき_翌日からは取得できない(string $deadline, int $expectedStatus, ?string $expectedMessage): void
    {
        // Arrange
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-02 12:00:00'));
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'attendance_deadline' => $deadline,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus($expectedStatus);
        $this->assertSame($expectedMessage, $response->json('message'));
    }

    /** @return array<string, array{string, int, ?string}> */
    public static function deadlineProvider(): array
    {
        return [
            '期限の翌日' => ['2026-08-01', 403, 'This action is unauthorized.'],
            '期限の当日' => ['2026-08-02', 200, null],
        ];
    }

    /** AC-PROG-012 */
    public function test_非公開のレッスンを受講済みでも進捗の件数に含まれない(): void
    {
        // Arrange — 非公開のレッスンだけを完了している状態
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $privateLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $publicLesson->id,
        ]);
        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $privateLesson->id,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert — 非公開のレッスンは分母にも分子にも含まれない
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_total_lessons', 1);
        $response->assertJsonPath('data.number_of_completed_lessons', 0);
        $response->assertJsonPath('data.number_of_completed_chapters', 0);
    }

    /** AC-PROG-007, AC-PROG-013 */
    public function test_一度受講済みになったレッスンは受講中に戻しても件数に数え続ける(): void
    {
        // Arrange — 完了日時が記録されたあとステータスだけ受講中に戻したレッスン
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lessonAttendance = LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
        ]);
        $lessonAttendance->changeStatus(LessonAttendanceStatusEnum::IN_ATTENDANCE);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert — 終えた事実は変わらないため完了として扱われる
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_completed_lessons', 1);
        $response->assertJsonPath('data.number_of_completed_chapters', 1);
        $response->assertJsonPath('data.continue_from', null);
    }
}
