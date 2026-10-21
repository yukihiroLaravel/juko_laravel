<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShowStatusTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-17 12:00:00'));
    }

    #[\Override]
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /**
     * 完了件数の集計期間
     *
     * @return array<string, array{string}>
     */
    public static function periods(): array
    {
        return [
            '当日' => ['today'],
            '当月' => ['month'],
        ];
    }

    /**
     * 修了率（受講生Aと受講生Bが完了した公開レッスンの数, 期待する修了率）
     *
     * @return array<string, array{int, int, int}>
     */
    public static function completionCounts(): array
    {
        return [
            '全員未修了' => [0, 0, 0],
            '2人のうち1人が修了' => [2, 1, 50],
            '全員修了' => [2, 2, 100],
        ];
    }

    /**
     * 期間の境界（期間, 境界の直前に完了した日時, 境界ちょうどに完了した日時）
     *
     * @return array<string, array{string, string, string}>
     */
    public static function periodBoundaries(): array
    {
        return [
            '当日0時0分0秒' => ['today', '2026-09-16 23:59:59', '2026-09-17 00:00:00'],
            '当月1日0時0分0秒' => ['month', '2026-08-31 23:59:59', '2026-09-01 00:00:00'],
        ];
    }

    /** AC-ANALYTICS-007 */
    #[DataProvider('periods')]
    public function test_当日または当月を指定して集計を取得できる(string $period): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => $period,
        ]));

        // Assert
        $response->assertStatus(200);
    }

    /** AC-ANALYTICS-007 */
    public function test_存在しない講座と不正な期間を指定すると422になる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => 99999,
            'period' => 'invalid',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'period',
        ]);
    }

    /** AC-AUTHZ-001 */
    public function test_担当ではない講師の講座は取得できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(403);
    }

    /** AC-ANALYTICS-002 */
    public function test_受講生がいない場合は平均進捗率と修了率が0になる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'average_progress_rate' => 0,
            'completion_rate' => 0,
        ]);
    }

    /** AC-ANALYTICS-002 */
    public function test_公開レッスンがない場合は平均進捗率が0になる(): void
    {
        // Arrange — 講座にチャプター/レッスンが無く、受講生のみ存在
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['average_progress_rate' => 0]);
    }

    /** AC-ANALYTICS-008・AC-ANALYTICS-010 */
    public function test_非公開レッスンは平均進捗率の計算に含まれない(): void
    {
        // Arrange — 公開2本 + 非公開2本、受講生1名が公開レッスン2本を完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);

        $publicLessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $privateLessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        // 公開Lesson 2件を完了
        foreach ($publicLessons as $lesson) {
            LessonAttendance::factory()->completed()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
            ]);
        }
        // 非公開Lesson はステータス遷移しないものの、レコード自体は存在する想定
        foreach ($privateLessons as $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
            ]);
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 2,
            'average_progress_rate' => 100,
        ]);
    }

    /** AC-ANALYTICS-008・AC-ANALYTICS-010 */
    public function test_平均進捗率は完了件数を受講生数と公開レッスン数の積で割り小数を切り捨てる(): void
    {
        // Arrange — 受講生2名 × レッスン4本、当日完了が5件 → floor(5 / (2 * 4) * 100) = 62
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(4)->create(['chapter_id' => $chapter->id]);

        $studentA = Student::factory()->create();
        $attendanceA = Attendance::factory()->create(['student_id' => $studentA->id, 'course_id' => $course->id]);
        $studentB = Student::factory()->create();
        $attendanceB = Attendance::factory()->create(['student_id' => $studentB->id, 'course_id' => $course->id]);

        // 受講生A: 4レッスンのうち3件を完了
        foreach ($lessons as $i => $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendanceA->id,
                'lesson_id' => $lesson->id,
                'status' => $i < 3
                    ? LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE
                    : LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
                'completed_at' => $i < 3 ? CarbonImmutable::now() : null,
            ]);
        }
        // 受講生B: 4レッスンのうち2件を完了
        foreach ($lessons as $i => $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendanceB->id,
                'lesson_id' => $lesson->id,
                'status' => $i < 2
                    ? LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE
                    : LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
                'completed_at' => $i < 2 ? CarbonImmutable::now() : null,
            ]);
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 5,
            'average_progress_rate' => 62,
        ]);
    }

    /** AC-ANALYTICS-011 */
    #[DataProvider('completionCounts')]
    public function test_公開レッスンをすべて完了した受講生の割合を修了率として返す(int $completedByA, int $completedByB, int $expectedRate): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create(['chapter_id' => $chapter->id]);

        foreach ([$completedByA, $completedByB] as $completedCount) {
            $student = Student::factory()->create();
            $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
            foreach ($lessons as $i => $lesson) {
                $lessonAttendance = LessonAttendance::factory();
                if ($i < $completedCount) {
                    $lessonAttendance = $lessonAttendance->completed();
                }
                $lessonAttendance->create([
                    'attendance_id' => $attendance->id,
                    'lesson_id' => $lesson->id,
                ]);
            }
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completion_rate' => $expectedRate]);
    }

    /** AC-ANALYTICS-011 */
    public function test_非公開レッスンは修了率の判定に含まれない(): void
    {
        // Arrange — 公開2本＋非公開1本、受講生が公開レッスン2本のみ完了 → 全公開レッスン完了扱い → 100
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);

        $publicLessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $privateLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        // 公開レッスンを完了
        foreach ($publicLessons as $lesson) {
            LessonAttendance::factory()->completed()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
            ]);
        }
        // 非公開レッスンは未完了のままレコードは作成（仕様上、受講登録時に全レッスン分作成される）
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $privateLesson->id,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completion_rate' => 100]);
    }

    /** AC-ANALYTICS-011・BR-SHARED-012 */
    public function test_段階が受講中に戻っても完了日時があれば修了率に含める(): void
    {
        // Arrange — 一度完了したレッスンの段階が学び直しで受講中に戻った状態。完了日時は一度記録したら消えない（BR-SHARED-012）
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        // 1件目: 完了済み
        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[0]->id,
        ]);
        // 2件目: 過去に完了し、段階が受講中に戻った（完了日時は残る）
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[1]->id,
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => now()->subDay(),
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completion_rate' => 100]);
    }

    /** AC-ANALYTICS-009 */
    public function test_下書きと非公開のチャプターは完了したチャプター件数に含めない(): void
    {
        // Arrange — 公開・下書き・非公開のチャプターに公開レッスンを1つずつ置き、受講生がすべて当日に完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);

        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
        ]);

        // 公開チャプター
        $publicChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);

        // 下書きチャプター
        $draftChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::DRAFT->value,
        ]);

        // 非公開チャプター
        $privateChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PRIVATE->value,
        ]);

        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $publicChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $draftChapterLesson = Lesson::factory()->create([
            'chapter_id' => $draftChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $privateChapterLesson = Lesson::factory()->create([
            'chapter_id' => $privateChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $student = Student::factory()->create();

        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        foreach ([$publicLesson, $draftChapterLesson, $privateChapterLesson] as $lesson) {
            LessonAttendance::factory()->completed()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
            ]);
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 1,
        ]);
    }

    /** AC-ANALYTICS-009 */
    public function test_非公開のレッスンが未完了でも公開レッスンをすべて完了したチャプターは完了したチャプター件数に含める(): void
    {
        // Arrange — 公開レッスンと非公開のレッスンを持つチャプターで、公開レッスンだけを当日に完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);

        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
        ]);

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);

        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);

        $student = Student::factory()->create();

        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $publicLesson->id,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 1,
        ]);
    }

    /** AC-ANALYTICS-009 */
    public function test_公開レッスンがないチャプターは完了したチャプター件数に含めない(): void
    {
        // Arrange — 非公開のレッスンだけを持つチャプターで、そのレッスンを当日に完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);

        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
        ]);

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);

        $privateLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);

        $student = Student::factory()->create();

        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $privateLesson->id,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 0,
        ]);
    }

    /** AC-ANALYTICS-009 */
    public function test_非公開のレッスンを当日に完了しても公開レッスンを当日に完了していないチャプターは当日の完了したチャプター件数に含めない(): void
    {
        // Arrange — 公開レッスンを40日前に、非公開のレッスンを当日に完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);

        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
        ]);

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);

        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $privateLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);

        $student = Student::factory()->create();

        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $publicLesson->id,
            'completed_at' => CarbonImmutable::now()->subDays(40),
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $privateLesson->id,
            'completed_at' => CarbonImmutable::now(),
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 0,
        ]);
    }

    /** AC-ANALYTICS-008 */
    #[DataProvider('periodBoundaries')]
    public function test_完了日時が期間の開始日時以降のレッスンだけを完了したレッスン件数に含める(string $period, string $beforeBoundary, string $atBoundary): void
    {
        // Arrange — 期間の開始日時の直前に完了したレッスンと、開始日時ちょうどに完了したレッスン
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create(['chapter_id' => $chapter->id]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[0]->id,
            'completed_at' => CarbonImmutable::parse($beforeBoundary),
        ]);
        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[1]->id,
            'completed_at' => CarbonImmutable::parse($atBoundary),
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => $period,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completed_lessons_count' => 1]);
    }

    /** AC-ANALYTICS-008 */
    public function test_学び直しで段階が受講中に戻ったレッスンも完了日時が当日なら当日の完了したレッスン件数に含める(): void
    {
        // Arrange — 当日に完了したあと、学び直しで段階を受講中に戻した
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-09-17 09:00:00'),
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completed_lessons_count' => 1]);
    }

    /** AC-ANALYTICS-008 */
    public function test_完了日時が記録されていないレッスンは段階が完了でも完了したレッスン件数に含めない(): void
    {
        // Arrange — 段階は完了だが、完了日時が記録されていない
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => null,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completed_lessons_count' => 0]);
    }

    /** AC-ANALYTICS-009 */
    public function test_チャプター内のすべてのレッスンを完了するとレッスンの数ではなくチャプター1件として完了したチャプター件数に含める(): void
    {
        // Arrange — 2つのレッスンを持つチャプターを、受講生1人が当日にすべて完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create(['chapter_id' => $chapter->id]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[0]->id,
            'completed_at' => CarbonImmutable::parse('2026-09-17 09:00:00'),
        ]);
        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[1]->id,
            'completed_at' => CarbonImmutable::parse('2026-09-17 10:00:00'),
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert — レッスン2件分ではなく、チャプター1件として数える
        $response->assertStatus(200);
        $response->assertJson(['completed_chapters_count' => 1]);
    }

    /** AC-ANALYTICS-009 */
    public function test_同じチャプターでも受講生ごとに完了したチャプター件数に含める(): void
    {
        // Arrange — 1つのレッスンを持つチャプターを、受講生2人がそれぞれ当日に完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);

        $studentA = Student::factory()->create();
        $attendanceA = Attendance::factory()->create(['student_id' => $studentA->id, 'course_id' => $course->id]);
        $studentB = Student::factory()->create();
        $attendanceB = Attendance::factory()->create(['student_id' => $studentB->id, 'course_id' => $course->id]);

        foreach ([$attendanceA, $attendanceB] as $attendance) {
            LessonAttendance::factory()->completed()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'completed_at' => CarbonImmutable::parse('2026-09-17 09:00:00'),
            ]);
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completed_chapters_count' => 2]);
    }

    /** AC-ANALYTICS-009 */
    public function test_最後のレッスンの完了が当日0時0分0秒以降のチャプターだけを当日の完了したチャプター件数に含める(): void
    {
        // Arrange — 最後のレッスンの完了が前日23時59分59秒のチャプターと、当日0時0分0秒のチャプター
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapterBefore = Chapter::factory()->create(['course_id' => $course->id]);
        $lessonsBefore = Lesson::factory()->count(2)->create(['chapter_id' => $chapterBefore->id]);
        $chapterAfter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessonsAfter = Lesson::factory()->count(2)->create(['chapter_id' => $chapterAfter->id]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        $completedAtByLesson = [
            [$lessonsBefore[0], '2026-09-16 10:00:00'],
            [$lessonsBefore[1], '2026-09-16 23:59:59'],
            [$lessonsAfter[0], '2026-09-16 10:00:00'],
            [$lessonsAfter[1], '2026-09-17 00:00:00'],
        ];
        foreach ($completedAtByLesson as [$lesson, $completedAt]) {
            LessonAttendance::factory()->completed()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'completed_at' => CarbonImmutable::parse($completedAt),
            ]);
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completed_chapters_count' => 1]);
    }

    /** AC-ANALYTICS-009 */
    public function test_すべてのレッスンを前月に完了したチャプターは当月に更新されても当月の完了したチャプター件数に含めない(): void
    {
        // Arrange — 前月にすべて完了し、受講状況は当日に更新されている
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create(['chapter_id' => $chapter->id]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        foreach ($lessons as $lesson) {
            LessonAttendance::factory()->completed()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'completed_at' => CarbonImmutable::parse('2026-08-20 10:00:00'),
                'updated_at' => CarbonImmutable::now(),
            ]);
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completed_chapters_count' => 0]);
    }

    /** AC-ANALYTICS-009 */
    public function test_段階が完了でも完了日時が記録されていないレッスンが残っているチャプターは完了したチャプター件数に含めない(): void
    {
        // Arrange — 1つ目のレッスンは当日に完了、2つ目は段階だけが完了で完了日時がない
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create(['chapter_id' => $chapter->id]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[0]->id,
            'completed_at' => CarbonImmutable::parse('2026-09-17 09:00:00'),
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[1]->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => null,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completed_chapters_count' => 0]);
    }

    /** AC-ANALYTICS-009 */
    public function test_学び直しで段階が受講中に戻ったレッスンがあってもすべてのレッスンに完了日時があるチャプターは完了したチャプター件数に含める(): void
    {
        // Arrange — 2つのレッスンを当日に完了し、2つ目は学び直しで段階を受講中に戻した
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create(['chapter_id' => $chapter->id]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[0]->id,
            'completed_at' => CarbonImmutable::parse('2026-09-17 09:00:00'),
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[1]->id,
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-09-17 10:00:00'),
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completed_chapters_count' => 1]);
    }

    /** AC-ANALYTICS-008・AC-ANALYTICS-010・AC-ANALYTICS-011 */
    public function test_非公開チャプターに属する公開レッスンは完了したレッスン件数と平均進捗率と修了率の集計に含めない(): void
    {
        // Arrange — 公開チャプターと、完了後に非公開にされたチャプターに公開レッスンが1件ずつあり、両方を当日に完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);

        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
        ]);

        $publicChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);

        $privateChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PRIVATE->value,
        ]);

        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $publicChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $lessonInPrivateChapter = Lesson::factory()->create([
            'chapter_id' => $privateChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $student = Student::factory()->create();

        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $publicLesson->id,
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessonInPrivateChapter->id,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 1,
            'average_progress_rate' => 100,
            'completion_rate' => 100,
        ]);
    }
}
