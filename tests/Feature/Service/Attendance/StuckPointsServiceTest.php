<?php

namespace Tests\Feature\Service\Attendance;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Dto\Instructor\Attendance\StuckLessonDto;
use App\Dto\Instructor\Attendance\StuckPointDto;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use App\Services\Attendance\StuckPointsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StuckPointsServiceTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01'));
    }

    #[\Override]
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_公開チャプターが1件も存在しない場合_空コレクションを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PRIVATE->value
        ]);

        // 受講生2人を期限内で登録
        $student1 = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $student2 = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);

        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());

    }

    public function test_公開レッスンが1件も存在しない場合_空コレクションを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value
        ]);

        // 受講生2人を期限内で登録
        $student1 = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $student2 = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);

        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());

    }

    public function test_受講期限が切れている場合_空コレクションを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);

        // 期限切れの受講生2人
        $student1 = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-30'),
        ]);
        $student2 = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-04-30'),
        ]);

        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());

    }

    public function test_受講生が1人の場合_空コレクションを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);

        // 受講生1人のみ
        $student = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);

        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());

    }

    public function test_受講済みのレッスンがない場合_最初の公開レッスンを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
            'title' => 'チャプター1',
        ]);
        $firstLesson = Lesson::factory()->create([
            'chapter_id' => $chapter1->id,
            'order' => 1,
            'title' => '最初のレッスン',
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter1->id,
            'order' => 2,
        ]);
        $chapter2 = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 2,
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter2->id,
            'order' => 1,
        ]);

        // 受講生2人がレッスン未完了
        $student1 = Student::factory()->create();
        $attendance1 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $student2 = Student::factory()->create();
        $attendance2 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $firstLesson->id,
            'attendance_id' => $attendance1->id,
            'status' => LessonAttendance::STATUS_IN_ATTENDANCE,
            'completed_at' => null,
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $firstLesson->id,
            'attendance_id' => $attendance2->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
            'completed_at' => null,
        ]);

        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert — 最初のチャプターの最初のレッスンが返る
        $this->assertCount(1, $result);
        /** @var StuckPointDto $stuckPoint */
        $stuckPoint = $result->first();
        $this->assertInstanceOf(StuckPointDto::class, $stuckPoint);
        $this->assertSame($chapter1->id, $stuckPoint->id);
        $this->assertSame('チャプター1', $stuckPoint->title);
        $this->assertCount(1, $stuckPoint->lessons);
        /** @var StuckLessonDto $stuckLesson */
        $stuckLesson = $stuckPoint->lessons->first();
        $this->assertInstanceOf(StuckLessonDto::class, $stuckLesson);
        $this->assertSame($firstLesson->id, $stuckLesson->id);
        $this->assertSame('最初のレッスン', $stuckLesson->title);

    }

    public function test_最多数が必要受講生数未満の場合_最初の公開レッスンを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
            'title' => 'チャプター1',
        ]);
        $firstLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 1,
            'title' => '最初のレッスン',
        ]);
        $secondLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);

        // 受講生2人。1人だけ secondLesson を完了（最多1人 < MINIMUM_REQUIRED_STUDENTS=2）
        $student1 = Student::factory()->create();
        $attendance1 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $student2 = Student::factory()->create();
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $secondLesson->id,
            'attendance_id' => $attendance1->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-05-02'),
        ]);

        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert — 最初のチャプターの最初のレッスンが返る
        $this->assertCount(1, $result);
        /** @var StuckPointDto $stuckPoint */
        $stuckPoint = $result->first();
        $this->assertSame($chapter->id, $stuckPoint->id);
        $this->assertSame('チャプター1', $stuckPoint->title);
        /** @var StuckLessonDto $stuckLesson */
        $stuckLesson = $stuckPoint->lessons->first();
        $this->assertSame($firstLesson->id, $stuckLesson->id);
        $this->assertSame('最初のレッスン', $stuckLesson->title);

    }

    public function test_受講済み最多数のレッスンが最終レッスンのみの場合_空コレクションを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $lastLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);

        // 受講生2人がともに最終レッスンのみ完了
        $student1 = Student::factory()->create();
        $attendance1 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $student2 = Student::factory()->create();
        $attendance2 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lastLesson->id,
            'attendance_id' => $attendance1->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-05-02'),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lastLesson->id,
            'attendance_id' => $attendance2->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-05-03'),
        ]);

        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());

    }

    public function test_受講済み最多数のレッスンが最終レッスン以外の場合_同一チャプターの次のレッスンを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
            'title' => 'チャプター1',
        ]);
        Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $targetLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);
        $lastLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 3,
            'title' => '最終レッスン',
        ]);

        // 受講生2人がともに targetLesson まで完了、1人は最終レッスンも完了
        $student1 = Student::factory()->create();
        $attendance1 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $student2 = Student::factory()->create();
        $attendance2 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $targetLesson->id,
            'attendance_id' => $attendance1->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-05-02'),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $targetLesson->id,
            'attendance_id' => $attendance2->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-05-03'),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lastLesson->id,
            'attendance_id' => $attendance1->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-05-04'),
        ]);

        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert — targetLesson の次の lastLesson が返る
        $this->assertCount(1, $result);
        /** @var StuckPointDto $stuckPoint */
        $stuckPoint = $result->first();
        $this->assertSame($chapter->id, $stuckPoint->id);
        $this->assertSame('チャプター1', $stuckPoint->title);
        /** @var StuckLessonDto $stuckLesson */
        $stuckLesson = $stuckPoint->lessons->first();
        $this->assertSame($lastLesson->id, $stuckLesson->id);
        $this->assertSame('最終レッスン', $stuckLesson->title);

    }

    public function test_受講済み最多数のレッスンが最終レッスン以外の場合_次のチャプターの最初のレッスンを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        $chapter2 = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 2,
            'title' => 'チャプター2',
        ]);
        Lesson::factory()->create(['chapter_id' => $chapter1->id, 'order' => 1]);
        $chapter1LastLesson = Lesson::factory()->create([
            'chapter_id' => $chapter1->id,
            'order' => 2,
        ]);
        $chapter2FirstLesson = Lesson::factory()->create([
            'chapter_id' => $chapter2->id,
            'order' => 1,
            'title' => 'チャプター2の最初のレッスン',
        ]);
        $courseLastLesson = Lesson::factory()->create([
            'chapter_id' => $chapter2->id,
            'order' => 2,
        ]);

        // 受講生2人がともに chapter1 の最終レッスンまで完了、1人は講座の最終レッスンも完了
        $student1 = Student::factory()->create();
        $attendance1 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $student2 = Student::factory()->create();
        $attendance2 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $chapter1LastLesson->id,
            'attendance_id' => $attendance1->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-05-02'),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $chapter1LastLesson->id,
            'attendance_id' => $attendance2->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-05-03'),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $courseLastLesson->id,
            'attendance_id' => $attendance1->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-05-04'),
        ]);

        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert — chapter2 の最初のレッスンが返る
        $this->assertCount(1, $result);
        /** @var StuckPointDto $stuckPoint */
        $stuckPoint = $result->first();
        $this->assertSame($chapter2->id, $stuckPoint->id);
        $this->assertSame('チャプター2', $stuckPoint->title);
        /** @var StuckLessonDto $stuckLesson */
        $stuckLesson = $stuckPoint->lessons->first();
        $this->assertSame($chapter2FirstLesson->id, $stuckLesson->id);
        $this->assertSame('チャプター2の最初のレッスン', $stuckLesson->title);

    }
}
