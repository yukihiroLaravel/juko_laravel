<?php

namespace Tests\Feature\Api\Student\LearningHistory;

use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use App\Model\StudentLoginHistory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 3, 12));
    }

    #[\Override]
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_自分の受講の総数を講座とレッスンとチャプターごとに取得できる(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.learning-history.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'window' => ['type', 'start', 'end'],
                'stats' => [
                    'courses' => ['completed', 'total'],
                    'lessons' => ['completed', 'total', 'last_completed_at'],
                    'chapters' => ['completed', 'total'],
                    'login_count',
                ],
            ],
        ]);
        $response->assertJsonPath('data.stats.courses.total', 1);
        $response->assertJsonPath('data.stats.lessons.total', 1);
        $response->assertJsonPath('data.stats.chapters.total', 1);
    }

    public function test_受講がない場合は件数がすべて0で最新の完了日時もない(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.learning-history.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.stats.courses.completed', 0);
        $response->assertJsonPath('data.stats.courses.total', 0);
        $response->assertJsonPath('data.stats.lessons.completed', 0);
        $response->assertJsonPath('data.stats.lessons.total', 0);
        $response->assertJsonPath('data.stats.lessons.last_completed_at', null);
        $response->assertJsonPath('data.stats.chapters.completed', 0);
        $response->assertJsonPath('data.stats.chapters.total', 0);
    }

    /** AC-ANALYTICS-035 */
    public function test_学習履歴取得_期間内に完了した講座のみカウントされる(): void
    {
        // Arrange
        $now = CarbonImmutable::now();

        $student = Student::factory()->create();
        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();

        // 期間内（10日前）に完了した講座
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course1->id,
            'completed_at' => $now->subDays(10),
        ]);
        // 期間外（40日前）に完了した講座
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course2->id,
            'completed_at' => $now->subDays(40),
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.learning-history.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.stats.courses.completed', 1);
        $response->assertJsonPath('data.stats.courses.total', 2);
    }

    /** AC-ANALYTICS-035 */
    public function test_学習履歴取得_期間内に完了したレッスンのみカウントされる(): void
    {
        // Arrange
        $now = CarbonImmutable::now();

        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        // 期間内に完了したレッスン
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson1->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => $now->subDays(5),
        ]);
        // 期間外に完了したレッスン
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson2->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => $now->subDays(40),
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.learning-history.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.stats.lessons.completed', 1);
        $response->assertJsonPath('data.stats.lessons.total', 2);
    }

    /** AC-ANALYTICS-036 */
    public function test_学習履歴取得_期間内の最新レッスン完了日時が返却される(): void
    {
        // Arrange
        $now = CarbonImmutable::now();

        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lesson3 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $latestCompletedAt = $now->subDays(2);

        // 期間内に完了したレッスン（複数）
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson1->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => $now->subDays(15),
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson2->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => $latestCompletedAt,
        ]);
        // 期間外に完了したレッスン（除外されること）
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson3->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => $now->subDays(40),
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.learning-history.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.stats.lessons.last_completed_at', $latestCompletedAt->format('Y-m-d H:i:s'));
    }

    /** AC-ANALYTICS-036 */
    public function test_学習履歴取得_期間内に完了したレッスンがない場合は最新の完了日時がない(): void
    {
        // Arrange
        $now = CarbonImmutable::now();

        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        // 期間外（40日前）に完了したレッスンのみ
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => $now->subDays(40),
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.learning-history.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.stats.lessons.last_completed_at', null);
    }

    /** AC-ANALYTICS-037 */
    public function test_学習履歴取得_全レッスン完了したチャプターのみカウントされる(): void
    {
        // Arrange
        $now = CarbonImmutable::now();

        $student = Student::factory()->create();
        $course = Course::factory()->create();

        // チャプター1: 全レッスン（2つ）が期間内に完了
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1a = Lesson::factory()->create(['chapter_id' => $chapter1->id]);
        $lesson1b = Lesson::factory()->create(['chapter_id' => $chapter1->id]);

        // チャプター2: 1つのレッスンのみ完了（もう1つは未完了）
        $chapter2 = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson2a = Lesson::factory()->create(['chapter_id' => $chapter2->id]);
        $lesson2b = Lesson::factory()->create(['chapter_id' => $chapter2->id]);

        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson1a->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => $now->subDays(5),
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson1b->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => $now->subDays(3),
        ]);

        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson2a->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => $now->subDays(5),
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson2b->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.learning-history.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.stats.chapters.completed', 1);
        $response->assertJsonPath('data.stats.chapters.total', 2);
    }

    /** AC-ANALYTICS-038 */
    public function test_学習履歴取得_期間内のログイン回数のみカウントされる(): void
    {
        // Arrange
        $now = CarbonImmutable::now();

        $student = Student::factory()->create();
        $otherStudent = Student::factory()->create();

        // 期間内（10日前）のログイン3件
        StudentLoginHistory::factory()->count(3)->create([
            'student_id' => $student->id,
            'logged_in_at' => $now->subDays(10),
        ]);
        // 期間外（40日前）のログイン2件
        StudentLoginHistory::factory()->count(2)->create([
            'student_id' => $student->id,
            'logged_in_at' => $now->subDays(40),
        ]);
        // 他の受講生のログイン（カウントされないこと）
        StudentLoginHistory::factory()->count(4)->create([
            'student_id' => $otherStudent->id,
            'logged_in_at' => $now->subDays(5),
        ]);

        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.learning-history.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.stats.login_count', 3);
    }

    public function test_未ログインの場合は学習履歴を取得できない(): void
    {
        // Act
        $response = $this->getJson(route('student.learning-history.index'));

        // Assert
        $response->assertStatus(401);
    }
}
