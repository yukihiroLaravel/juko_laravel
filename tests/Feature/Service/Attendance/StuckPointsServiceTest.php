<?php

namespace Tests\Feature\Service\Attendance;

use App\Dto\Instructor\Attendance\StuckLessonDto;
use App\Dto\Instructor\Attendance\StuckPointDto;
use App\Enums\Course\DeadlineTypeEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use App\Services\Attendance\StuckPointsService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StuckPointsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-05-01'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_受講期限が切れている場合_nullを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'deadline_type' => DeadlineTypeEnum::FIXED_DATE,
        ]);
        CourseDeadline::factory()->create([
            'course_id' => $course->id,
            'fixed_date' => CarbonImmutable::parse('2026-04-30'),
        ]);
        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert
        $this->assertNull($result);
    }

    public function test_公開チャプターが1件も存在しない場合_nullを返す(): void
    {
        // Arrange
        $course = $this->createCourseWithDeadline();
        Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => Chapter::STATUS_PRIVATE,
        ]);
        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert
        $this->assertNull($result);
    }

    public function test_公開レッスンが1件も存在しない場合_nullを返す(): void
    {
        // Arrange
        $course = $this->createCourseWithDeadline();
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => Lesson::STATUS_PRIVATE,
        ]);
        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert
        $this->assertNull($result);
    }

    public function test_受講生が1人の場合_nullを返す(): void
    {
        // Arrange
        $course = $this->createCourseWithDeadline();
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
        ]);
        Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => Student::factory()->create()->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert
        $this->assertNull($result);
    }

    public function test_受講済みのレッスンがない場合_最初の公開レッスンのdtoを返す(): void
    {
        // Arrange
        $course = $this->createCourseWithDeadline();

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
        ]);
        $firstLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 1,
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);

        $this->createTwoAttendances($course);

        $service = new StuckPointsService;

        // Act
        $result = $service($course->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(StuckPointDto::class, $result->first());

        /** @var StuckPointDto $stuckPoint */
        $stuckPoint = $result->first();
        $this->assertSame($chapter->id, $stuckPoint->id);
        $this->assertSame($chapter->title, $stuckPoint->title);
        $this->assertCount(1, $stuckPoint->lessons);
        $this->assertInstanceOf(StuckLessonDto::class, $stuckPoint->lessons->first());

        /** @var StuckLessonDto $stuckLesson */
        $stuckLesson = $stuckPoint->lessons->first();
        $this->assertSame($firstLesson->id, $stuckLesson->id);
        $this->assertSame($firstLesson->title, $stuckLesson->title);
    }

    public function test_受講済み最多数のレッスンが最終レッスンのみの場合_nullを返す(): void
    {
        // Arrange
        $course = $this->createCourseWithDeadline();

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
        ]);

        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 1,
        ]);
        $lastLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);

        [$attendance1, $attendance2] = $this->createTwoAttendances($course);

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
        $this->assertNull($result);
    }

    public function test_受講済み最多数のレッスンが最終レッスン以外の場合_受講済み最多数のレッスンのdtoを返す(): void
    {
        // Arrange
        $course = $this->createCourseWithDeadline();

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 1,
        ]);
        $targetLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);
        $lastLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 3,
        ]);

        [$attendance1, $attendance2] = $this->createTwoAttendances($course);

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

        // Assert
        $this->assertNotNull($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(StuckPointDto::class, $result->first());

        /** @var StuckPointDto $stuckPoint */
        $stuckPoint = $result->first();
        $this->assertSame($chapter->id, $stuckPoint->id);
        $this->assertSame($chapter->title, $stuckPoint->title);
        $this->assertCount(1, $stuckPoint->lessons);
        $this->assertInstanceOf(StuckLessonDto::class, $stuckPoint->lessons->first());

        /** @var StuckLessonDto $stuckLesson */
        $stuckLesson = $stuckPoint->lessons->first();
        $this->assertSame($targetLesson->id, $stuckLesson->id);
        $this->assertSame($targetLesson->title, $stuckLesson->title);
    }

    private function createCourseWithDeadline(): Course
    {
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        CourseDeadline::factory()->create([
            'course_id' => $course->id,
            'fixed_date' => CarbonImmutable::parse('2026-05-31'),
        ]);

        return $course;
    }

    /**
     * @return array{0: Attendance, 1: Attendance}
     */
    private function createTwoAttendances(Course $course): array
    {
        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();

        $attendance1 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student1->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);
        $attendance2 = Attendance::factory()->create([
            'course_id' => $course->id,
            'student_id' => $student2->id,
            'attendance_deadline' => CarbonImmutable::parse('2026-05-20'),
        ]);

        return [$attendance1, $attendance2];
    }
}
