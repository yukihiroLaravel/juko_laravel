<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Course\DeadlineTypeEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\ManageInstructor;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHZ-002 */
    public function test_マネージャーは自分の講座の受講状況を取得できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
    }

    /** AC-AUTHZ-002 */
    public function test_マネージャーは配下の講師の講座の受講状況を取得できる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
    }

    /** AC-AUTHZ-001 */
    public function test_講師は自分の講座の受講状況を取得できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_受講状況の詳細に講座と受講人数が含まれる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'attendance_id',
                'attendance_deadline',
                'days_until_deadline',
                'course' => [
                    'course_id',
                    'title',
                    'image',
                    'status',
                    'capacity',
                    'deadline_type',
                    'course_deadline',
                ],
                'students_count',
            ],
        ]);
        $response->assertJson([
            'data' => [
                'attendance_id' => $attendance->id,
                'course' => [
                    'course_id' => $course->id,
                    'title' => $course->title,
                    'status' => $course->status->value,
                ],
                'students_count' => 1,
            ],
        ]);
    }

    /**
     * @return array<string, array{int|null}>
     */
    public static function capacities(): array
    {
        return [
            '定員あり' => [30],
            '定員なし' => [null],
        ];
    }

    #[DataProvider('capacities')]
    public function test_講座の定員をそのまま返し_定員を設定していなければ定員なしで返す(?int $capacity): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => $capacity,
        ]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.course.capacity', $capacity);
    }

    /** AC-ANALYTICS-013 */
    public function test_受講生が複数いる場合は受講人数に全員を数える(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $attendances = Attendance::factory()->count(3)->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendances->first()->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'students_count' => 3,
            ],
        ]);
    }

    /** AC-AUTHZ-010 */
    public function test_受講期限が過ぎた受講の受講状況は取得できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::yesterday(),
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
    }

    /** AC-AUTHZ-001 */
    public function test_他の講師の講座の受講状況は取得できない(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    /** AC-AUTHZ-003 */
    public function test_マネージャーは配下でない講師の講座の受講状況は取得できない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $unrelatedInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $unrelatedInstructor->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_削除済みの受講の受講状況は取得できない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $attendanceId = $attendance->id;
        $attendance->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendanceId]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attendance_id']);
    }

    /**
     * @return array<string, array{int|string}>
     */
    public static function invalidAttendanceIds(): array
    {
        return [
            '存在しない受講' => [99999],
            '数値でない値' => ['aaa'],
        ];
    }

    #[DataProvider('invalidAttendanceIds')]
    public function test_受講の指定が不正な場合は取得できない(int|string $attendanceId): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendanceId]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attendance_id']);
    }

    public function test_講座の期限の決め方が特定の日の場合は期限日を返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE->value,
        ]);
        CourseDeadline::factory()->create([
            'course_id' => $course->id,
            'fixed_date' => '2026-12-31',
        ]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'course' => [
                    'deadline_type' => 'fixed_date',
                ],
            ],
        ]);
        $response->assertJsonPath('data.course.course_deadline.fixed_date', '2026-12-31');
    }

    /** AC-ANALYTICS-013 */
    public function test_受講期限が過ぎた受講生は受講人数に含めない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $activeAttendance = Attendance::factory()->create([
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::tomorrow(),
        ]);
        Attendance::factory()->create([
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::yesterday(),
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $activeAttendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'students_count' => 1,
            ],
        ]);
    }

    /** AC-ANALYTICS-013 */
    public function test_受講期限が当日の受講生は受講人数に含める(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Attendance::factory()->create([
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::today(),
        ]);
        $futureAttendance = Attendance::factory()->create([
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::tomorrow(),
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $futureAttendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'students_count' => 2,
            ],
        ]);
    }

    /** AC-ANALYTICS-013 */
    public function test_受講期限のない受講生は受講人数に含める(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Attendance::factory()->create([
            'course_id' => $course->id,
            'attendance_deadline' => null,
        ]);
        $activeAttendance = Attendance::factory()->create([
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::tomorrow(),
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $activeAttendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'students_count' => 2,
            ],
        ]);
    }

    /** AC-ANALYTICS-014 */
    public function test_チャプターごとに名称と並びと完了人数を返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'chapters' => [
                    '*' => [
                        'chapter_id',
                        'title',
                        'order',
                        'status',
                        'completed_students_count',
                    ],
                ],
            ],
        ]);
    }

    /** AC-ANALYTICS-014 */
    public function test_チャプター内の全レッスンを完了した受講生はチャプターの完了人数に数える(): void
    {
        // Arrange — 2レッスンの両方を完了した受講生
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        LessonAttendance::factory()->completed()->create([
            'lesson_id' => $lesson1->id,
            'attendance_id' => $attendance->id,
        ]);
        LessonAttendance::factory()->completed()->create([
            'lesson_id' => $lesson2->id,
            'attendance_id' => $attendance->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters.0.chapter_id', $chapter->id);
        $response->assertJsonPath('data.chapters.0.completed_students_count', 1);
    }

    /** AC-ANALYTICS-014 */
    public function test_チャプター内に未完了のレッスンが残る受講生はチャプターの完了人数に数えない(): void
    {
        // Arrange — 2レッスンのうち1つだけ完了した受講生
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        LessonAttendance::factory()->completed()->create([
            'lesson_id' => $lesson1->id,
            'attendance_id' => $attendance->id,
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson2->id,
            'attendance_id' => $attendance->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters.0.completed_students_count', 0);
    }

    /** AC-ANALYTICS-013・AC-ANALYTICS-014 */
    public function test_受講期限が過ぎた受講生は全レッスンを完了していてもチャプターの完了人数に数えない(): void
    {
        // Arrange — 期限内と期限切れの受講生が、どちらも全レッスンを完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);

        $activeAttendance = Attendance::factory()->create([
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::tomorrow(),
        ]);
        LessonAttendance::factory()->completed()->create([
            'lesson_id' => $lesson->id,
            'attendance_id' => $activeAttendance->id,
        ]);

        $expiredAttendance = Attendance::factory()->create([
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::yesterday(),
        ]);
        LessonAttendance::factory()->completed()->create([
            'lesson_id' => $lesson->id,
            'attendance_id' => $expiredAttendance->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $activeAttendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters.0.completed_students_count', 1);
    }

    /** AC-ANALYTICS-015 */
    public function test_レッスンを1つも持たないチャプターはチャプター一覧に含めない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Chapter::factory()->create(['course_id' => $course->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters', []);
    }

    /** AC-ANALYTICS-014 */
    public function test_複数のチャプターの完了人数をチャプターごとに返す(): void
    {
        // Arrange — 2チャプターのうち、受講生はチャプター1だけ全レッスンを完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        $chapter2 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 2]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter1->id]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter2->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        LessonAttendance::factory()->completed()->create([
            'lesson_id' => $lesson1->id,
            'attendance_id' => $attendance->id,
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson2->id,
            'attendance_id' => $attendance->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters.0.chapter_id', $chapter1->id);
        $response->assertJsonPath('data.chapters.0.completed_students_count', 1);
        $response->assertJsonPath('data.chapters.1.chapter_id', $chapter2->id);
        $response->assertJsonPath('data.chapters.1.completed_students_count', 0);
    }

    /** AC-ANALYTICS-014 */
    public function test_完了日時が未設定のレッスンは段階が完了でもチャプターの完了人数に数えない(): void
    {
        // Arrange — 段階は完了だが、完了日時が記録されていないレッスン
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson->id,
            'attendance_id' => $attendance->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => null,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters.0.completed_students_count', 0);
    }

    /**
     * 除外されるチャプターのステータス
     *
     * @return array<string, array{ChapterStatusEnum}>
     */
    public static function excludedChapterStatuses(): array
    {
        return [
            '下書き' => [ChapterStatusEnum::DRAFT],
            '非公開' => [ChapterStatusEnum::PRIVATE],
        ];
    }

    /**
     * 完了人数の分子分母から除外されるレッスンのステータス
     *
     * @return array<string, array{LessonStatusEnum}>
     */
    public static function excludedLessonStatuses(): array
    {
        return [
            '下書き' => [LessonStatusEnum::DRAFT],
            '非公開' => [LessonStatusEnum::PRIVATE],
        ];
    }

    /** AC-ANALYTICS-014・AC-ANALYTICS-015 */
    #[DataProvider('excludedChapterStatuses')]
    public function test_公開ではないチャプターは受講状況詳細のチャプター一覧に含めない(ChapterStatusEnum $status): void
    {
        // Arrange — 公開チャプターと、対象ステータスのチャプター（どちらも公開レッスンあり）
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $publicChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => ChapterStatusEnum::PUBLIC]);
        Lesson::factory()->create(['chapter_id' => $publicChapter->id, 'status' => LessonStatusEnum::PUBLIC]);
        $nonPublicChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => $status]);
        Lesson::factory()->create(['chapter_id' => $nonPublicChapter->id, 'status' => LessonStatusEnum::PUBLIC]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.chapters');
        $response->assertJsonPath('data.chapters.0.chapter_id', $publicChapter->id);
    }

    /** AC-ANALYTICS-014 */
    #[DataProvider('excludedLessonStatuses')]
    public function test_公開チャプター内の公開ではないレッスンはチャプターの完了人数の対象から外す(LessonStatusEnum $status): void
    {
        // Arrange — 公開レッスンと対象ステータスのレッスンを持つ公開チャプター。受講生は公開レッスンだけ完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => ChapterStatusEnum::PUBLIC]);
        $publicLesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => LessonStatusEnum::PUBLIC]);
        Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => $status]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        LessonAttendance::factory()->completed()->create([
            'lesson_id' => $publicLesson->id,
            'attendance_id' => $attendance->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters.0.completed_students_count', 1);
    }

    /** AC-ANALYTICS-015 */
    #[DataProvider('excludedLessonStatuses')]
    public function test_公開レッスンを持たない公開チャプターは一覧に含めず残りは連続した添字で返す(LessonStatusEnum $status): void
    {
        // Arrange — 順番に、公開レッスンあり・対象ステータスのレッスンのみ・公開レッスンありの公開チャプター
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1, 'status' => ChapterStatusEnum::PUBLIC]);
        Lesson::factory()->create(['chapter_id' => $chapter1->id, 'status' => LessonStatusEnum::PUBLIC]);
        $chapter2 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 2, 'status' => ChapterStatusEnum::PUBLIC]);
        Lesson::factory()->create(['chapter_id' => $chapter2->id, 'status' => $status]);
        $chapter3 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 3, 'status' => ChapterStatusEnum::PUBLIC]);
        Lesson::factory()->create(['chapter_id' => $chapter3->id, 'status' => LessonStatusEnum::PUBLIC]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $this->assertTrue(array_is_list($response->json('data.chapters')));
        $response->assertJsonCount(2, 'data.chapters');
        $response->assertJsonPath('data.chapters.0.chapter_id', $chapter1->id);
        $response->assertJsonPath('data.chapters.1.chapter_id', $chapter3->id);
    }

    /** AC-ANALYTICS-014・AC-ANALYTICS-015 */
    public function test_公開チャプターがなければチャプター一覧は空になる(): void
    {
        // Arrange — 下書きと非公開のチャプターだけ（どちらも公開レッスンあり）
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $draftChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => ChapterStatusEnum::DRAFT]);
        Lesson::factory()->create(['chapter_id' => $draftChapter->id, 'status' => LessonStatusEnum::PUBLIC]);
        $privateChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => ChapterStatusEnum::PRIVATE]);
        Lesson::factory()->create(['chapter_id' => $privateChapter->id, 'status' => LessonStatusEnum::PUBLIC]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters', []);
        $response->assertJsonPath('data.students_count', 1);
    }
}
