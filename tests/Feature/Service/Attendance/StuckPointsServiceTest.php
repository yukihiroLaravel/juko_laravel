<?php

namespace Tests\Feature\Service\Attendance;

use App\Dto\Common\Attendance\StuckLessonDto;
use App\Dto\Common\Attendance\StuckPointDto;
use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
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

    /** AC-ANALYTICS-017 */
    public function test_公開チャプターが1つもないときはつまずき箇所なし(): void
    {
        // Arrange
        $course = Course::factory()->create();
        Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PRIVATE->value,
        ]);
        $this->enrollStudents($course, 2);

        // Act
        $result = (new StuckPointsService)($course->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    /** AC-ANALYTICS-017 */
    public function test_公開レッスンが1つもないときはつまずき箇所なし(): void
    {
        // Arrange
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);
        $this->enrollStudents($course, 2);

        // Act
        $result = (new StuckPointsService)($course->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    /** AC-ANALYTICS-018 */
    public function test_受講期限を過ぎた受講生は数えず有効な受講生がいないときはつまずき箇所なし(): void
    {
        // Arrange
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $this->enrollStudents($course, 2, '2026-04-30');

        // Act
        $result = (new StuckPointsService)($course->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    /** AC-ANALYTICS-018 */
    public function test_受講期限を過ぎていない受講生が1人のときはつまずき箇所なし(): void
    {
        // Arrange
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $this->enrollStudents($course, 1);

        // Act
        $result = (new StuckPointsService)($course->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    /** AC-ANALYTICS-019 */
    public function test_完了した受講生がいないときは最初の公開レッスンを返す(): void
    {
        // Arrange
        $course = Course::factory()->create();
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
        [$attendance1, $attendance2] = $this->enrollStudents($course, 2);
        LessonAttendance::factory()->create([
            'lesson_id' => $firstLesson->id,
            'attendance_id' => $attendance1->id,
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => null,
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $firstLesson->id,
            'attendance_id' => $attendance2->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
            'completed_at' => null,
        ]);

        // Act
        $result = (new StuckPointsService)($course->id);

        // Assert
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

    /** AC-ANALYTICS-019 */
    public function test_完了者が最も多いレッスンの完了者が2人に満たないときは最初の公開レッスンを返す(): void
    {
        // Arrange
        $course = Course::factory()->create();
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
        // 受講生2人のうち、2つ目のレッスンを完了したのは1人だけ
        [$attendance1] = $this->enrollStudents($course, 2);
        $this->completeLesson($secondLesson, $attendance1, '2026-05-02');

        // Act
        $result = (new StuckPointsService)($course->id);

        // Assert
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

    /** AC-ANALYTICS-020 */
    public function test_完了者が最も多いレッスンが最終の公開レッスンだけのときはつまずき箇所なし(): void
    {
        // Arrange
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $lastLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);
        [$attendance1, $attendance2] = $this->enrollStudents($course, 2);
        $this->completeLesson($lastLesson, $attendance1, '2026-05-02');
        $this->completeLesson($lastLesson, $attendance2, '2026-05-03');

        // Act
        $result = (new StuckPointsService)($course->id);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    /** AC-ANALYTICS-021 */
    public function test_完了者が最も多いレッスンの次のレッスンが同じチャプターにあるときはそのレッスンを返す(): void
    {
        // Arrange
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'order' => 1,
            'title' => 'チャプター1',
        ]);
        Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $mostCompletedLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
        ]);
        $lastLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 3,
            'title' => '最終レッスン',
        ]);
        // 2人が2つ目のレッスンを完了し、1人は3つ目（最終）も完了している
        [$attendance1, $attendance2] = $this->enrollStudents($course, 2);
        $this->completeLesson($mostCompletedLesson, $attendance1, '2026-05-02');
        $this->completeLesson($mostCompletedLesson, $attendance2, '2026-05-03');
        $this->completeLesson($lastLesson, $attendance1, '2026-05-04');

        // Act
        $result = (new StuckPointsService)($course->id);

        // Assert
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

    /** AC-ANALYTICS-021 */
    public function test_完了者が最も多いレッスンがチャプターの最後のときは次のチャプターの最初のレッスンを返す(): void
    {
        // Arrange
        $course = Course::factory()->create();
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
        // 2人がチャプター1の最後のレッスンを完了し、1人は講座の最終レッスンも完了している
        [$attendance1, $attendance2] = $this->enrollStudents($course, 2);
        $this->completeLesson($chapter1LastLesson, $attendance1, '2026-05-02');
        $this->completeLesson($chapter1LastLesson, $attendance2, '2026-05-03');
        $this->completeLesson($courseLastLesson, $attendance1, '2026-05-04');

        // Act
        $result = (new StuckPointsService)($course->id);

        // Assert
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

    /**
     * 受講生を指定の人数だけ講座に受講させる。受講期限の既定は、テスト内の現在日時（2026-05-01）より後。
     *
     * @return list<Attendance>
     */
    private function enrollStudents(Course $course, int $count, string $deadline = '2026-05-20'): array
    {
        $attendances = [];
        for ($i = 0; $i < $count; $i++) {
            $attendances[] = Attendance::factory()->create([
                'course_id' => $course->id,
                'student_id' => Student::factory()->create()->id,
                'attendance_deadline' => CarbonImmutable::parse($deadline),
            ]);
        }

        return $attendances;
    }

    private function completeLesson(Lesson $lesson, Attendance $attendance, string $completedAt): void
    {
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson->id,
            'attendance_id' => $attendance->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse($completedAt),
        ]);
    }
}
