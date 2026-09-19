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

    public function test_当日の出席状況を取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_今月の出席状況を取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_無効のパラメータ(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
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

    public function test_権限のない講師_失敗(): void
    {
        // Arrange — 他の講師の講座を指定
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_受講生がいない場合_平均進捗率は0(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
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
        $response->assertJson(['average_progress_rate' => 0]);
    }

    public function test_レッスンが0件の場合_平均進捗率は0(): void
    {
        // Arrange — 講座にチャプター/レッスンが無く、受講生のみ存在
        $instructor = Instructor::factory()->create();
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
        // 母数 = 公開Lesson 2本 × 受講生1名 = 2、完了 = 2 → 100%
        $instructor = Instructor::factory()->create();
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

        // Assert — 非公開Lessonは母数・分子から除外され、completed: 2 / (1 × 2) × 100 = 100
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 2,
            'average_progress_rate' => 100,
        ]);
    }

    /** AC-ANALYTICS-008・AC-ANALYTICS-010 */
    public function test_平均進捗率の計算が正しい(): void
    {
        // Arrange — 受講生2名 × レッスン4本、当日完了が5件 → floor(5 / (2 * 4) * 100) = 62
        $instructor = Instructor::factory()->create();
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

        // Assert — completed: 5 / (students: 2 × lessons: 4) × 100 = 62.5 → floor → 62
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 5,
            'average_progress_rate' => 62,
        ]);
    }

    public function test_受講生がいない場合_修了率は0(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
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
        $response->assertJson(['completion_rate' => 0]);
    }

    public function test_全員未修了の場合_修了率は0(): void
    {
        // Arrange — 受講生2名、全員全公開レッスン未完了
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $studentA = Student::factory()->create();
        $attendanceA = Attendance::factory()->create(['student_id' => $studentA->id, 'course_id' => $course->id]);
        $studentB = Student::factory()->create();
        $attendanceB = Attendance::factory()->create(['student_id' => $studentB->id, 'course_id' => $course->id]);

        foreach ([$attendanceA, $attendanceB] as $attendance) {
            foreach ($lessons as $lesson) {
                LessonAttendance::factory()->create([
                    'attendance_id' => $attendance->id,
                    'lesson_id' => $lesson->id,
                    'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
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
        $response->assertJson(['completion_rate' => 0]);
    }

    public function test_一部修了の場合_修了率の計算が正しい(): void
    {
        // Arrange — 受講生2名のうち1名が全公開レッスン完了、もう1名は一部のみ → floor(1/2*100) = 50
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        // 受講生A: 全公開レッスンを完了
        $studentA = Student::factory()->create();
        $attendanceA = Attendance::factory()->create(['student_id' => $studentA->id, 'course_id' => $course->id]);
        foreach ($lessons as $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendanceA->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
                'completed_at' => now(),
            ]);
        }

        // 受講生B: 1件のみ完了
        $studentB = Student::factory()->create();
        $attendanceB = Attendance::factory()->create(['student_id' => $studentB->id, 'course_id' => $course->id]);
        foreach ($lessons as $i => $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendanceB->id,
                'lesson_id' => $lesson->id,
                'status' => $i === 0
                    ? LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE
                    : LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
                'completed_at' => $i === 0 ? now() : null,
            ]);
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert — completed_students: 1 / students: 2 × 100 = 50
        $response->assertStatus(200);
        $response->assertJson(['completion_rate' => 50]);
    }

    public function test_全員修了の場合_修了率は100(): void
    {
        // Arrange — 受講生2名、全員が全公開レッスン完了
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $studentA = Student::factory()->create();
        $attendanceA = Attendance::factory()->create(['student_id' => $studentA->id, 'course_id' => $course->id]);
        $studentB = Student::factory()->create();
        $attendanceB = Attendance::factory()->create(['student_id' => $studentB->id, 'course_id' => $course->id]);

        foreach ([$attendanceA, $attendanceB] as $attendance) {
            foreach ($lessons as $lesson) {
                LessonAttendance::factory()->create([
                    'attendance_id' => $attendance->id,
                    'lesson_id' => $lesson->id,
                    'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
                    'completed_at' => now(),
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
        $response->assertJson(['completion_rate' => 100]);
    }

    public function test_非公開レッスンは修了率の判定に含まれない(): void
    {
        // Arrange — 公開2本＋非公開1本、受講生が公開レッスン2本のみ完了 → 全公開レッスン完了扱い → 100
        $instructor = Instructor::factory()->create();
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
            LessonAttendance::factory()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
                'completed_at' => now(),
            ]);
        }
        // 非公開レッスンは未完了のままレコードは作成（仕様上、受講登録時に全レッスン分作成される）
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $privateLesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert — 非公開レッスンは判定外なので、公開レッスンを全完了で修了扱い
        $response->assertStatus(200);
        $response->assertJson(['completion_rate' => 100]);
    }

    public function test_ステータスが未完了でも完了日時があれば修了率に含まれる(): void
    {
        // Arrange — 受講生が一度完了後に再学習でステータスが戻った状態を再現
        // completed_at は単調なので残ったまま → 修了扱い
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        // 1件目: 完了済み
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[0]->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => now(),
        ]);
        // 2件目: 過去に完了したが再学習でステータスが in_attendance に戻った状態（completed_at は残る）
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

        // Assert — 完了日時が両方設定されているので全公開レッスン修了扱い
        $response->assertStatus(200);
        $response->assertJson(['completion_rate' => 100]);
    }

    /** AC-ANALYTICS-009 */
    public function test_下書きと非公開のチャプターは完了したチャプター件数に含めない(): void
    {
        // Arrange — 公開・下書き・非公開のチャプターに公開レッスンを1つずつ置き、受講生がすべて当日に完了
        $instructor = Instructor::factory()->create();

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
        $instructor = Instructor::factory()->create();

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
        $instructor = Instructor::factory()->create();

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
        $instructor = Instructor::factory()->create();

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
    public function test_完了日時が当日0時0分0秒以降のレッスンだけを当日の完了したレッスン件数に含める(): void
    {
        // Arrange — 前日23時59分59秒に完了したレッスンと、当日0時0分0秒に完了したレッスン（受講状況はどちらも当日に更新）
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create(['chapter_id' => $chapter->id]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[0]->id,
            'completed_at' => CarbonImmutable::parse('2026-09-16 23:59:59'),
        ]);
        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[1]->id,
            'completed_at' => CarbonImmutable::parse('2026-09-17 00:00:00'),
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
    public function test_完了日時が当月1日0時0分0秒以降のレッスンだけを当月の完了したレッスン件数に含める(): void
    {
        // Arrange — 前月末日23時59分59秒に完了したレッスンと、当月1日0時0分0秒に完了したレッスン
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create(['chapter_id' => $chapter->id]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[0]->id,
            'completed_at' => CarbonImmutable::parse('2026-08-31 23:59:59'),
        ]);
        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[1]->id,
            'completed_at' => CarbonImmutable::parse('2026-09-01 00:00:00'),
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completed_lessons_count' => 1]);
    }

    /** AC-ANALYTICS-008 */
    public function test_学び直しで段階が受講中に戻ったレッスンも完了日時が当日なら当日の完了したレッスン件数に含める(): void
    {
        // Arrange — 当日に完了したあと、学び直しで段階を受講中に戻した
        $instructor = Instructor::factory()->create();
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
        $instructor = Instructor::factory()->create();
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
        $instructor = Instructor::factory()->create();
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
        $instructor = Instructor::factory()->create();
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
        $instructor = Instructor::factory()->create();
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
        $instructor = Instructor::factory()->create();
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
        $instructor = Instructor::factory()->create();
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
        $instructor = Instructor::factory()->create();
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
}
