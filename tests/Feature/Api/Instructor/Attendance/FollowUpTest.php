<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Enums\Chapter\StatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use App\Model\StudentLoginHistory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 日数の指定と、対象になる・ならない受講生の最終ログインからの経過日数
     *
     * @return array<string, array{int, int, int}>
     */
    public static function loginIntervals(): array
    {
        return [
            '10日' => [10, 15, 5],
            '180日' => [180, 200, 100],
        ];
    }

    /**
     * 対象講座の受講生の最終ログインからの経過日数（nullは一度もログインしていない）
     *
     * @return array<string, array{?int}>
     */
    public static function inCourseStudentLogins(): array
    {
        return [
            'ログイン履歴あり' => [20],
            '一度もログインしていない' => [null],
        ];
    }

    /**
     * 先頭のチャプターを判定の対象外にする状態（チャプターの状態, レッスンの状態）
     *
     * @return array<string, array{string, string}>
     */
    public static function ineligibleLeadingChapters(): array
    {
        return [
            '下書きのチャプター' => [StatusEnum::DRAFT->value, LessonStatusEnum::PUBLIC->value],
            '非公開のレッスンだけを持つチャプター' => [StatusEnum::PUBLIC->value, LessonStatusEnum::PRIVATE->value],
        ];
    }

    /**
     * 422 になる入力
     *
     * @return array<string, array{bool, array<string, int>, string}>
     */
    public static function invalidInputs(): array
    {
        return [
            '日数を指定しない' => [true, [], 'days'],
            '日数が 1 未満' => [true, ['days' => 0], 'days'],
            '存在しない講座' => [false, ['days' => 10], 'course_id'],
        ];
    }

    /** AC-ANALYTICS-024 */
    #[DataProvider('loginIntervals')]
    public function test_指定した日数以上ログインがない受講生だけを返す(int $days, int $targetDaysAgo, int $nonTargetDaysAgo): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        $studentTarget = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentTarget->id, 'course_id' => $course->id]);
        StudentLoginHistory::factory()->create([
            'student_id' => $studentTarget->id,
            'logged_in_at' => CarbonImmutable::now()->subDays($targetDaysAgo),
        ]);

        $studentNonTarget = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentNonTarget->id, 'course_id' => $course->id]);
        StudentLoginHistory::factory()->create([
            'student_id' => $studentNonTarget->id,
            'logged_in_at' => CarbonImmutable::now()->subDays($nonTargetDaysAgo),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => $days,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['student_id' => $studentTarget->id]);
        $response->assertJsonMissing(['student_id' => $studentNonTarget->id]);
    }

    /** AC-ANALYTICS-024・AC-ANALYTICS-026 */
    public function test_一度もログインしていない受講生は最終ログイン日時と経過日数なしで返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // 一度もログインしていない受講生（対象）
        $studentNeverLoggedIn = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentNeverLoggedIn->id, 'course_id' => $course->id]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 10,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'student_id' => $studentNeverLoggedIn->id,
            'last_login_at' => null,
            'days_since_login' => null,
        ]);
    }

    /** AC-ANALYTICS-024 */
    #[DataProvider('inCourseStudentLogins')]
    public function test_ほかの講座の受講生と受講していない生徒は含めない(?int $inCourseLoginDaysAgo): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        $studentInCourse = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentInCourse->id, 'course_id' => $course->id]);
        if ($inCourseLoginDaysAgo !== null) {
            StudentLoginHistory::factory()->create([
                'student_id' => $studentInCourse->id,
                'logged_in_at' => CarbonImmutable::now()->subDays($inCourseLoginDaysAgo),
            ]);
        }

        // ログイン履歴のない、ほかの講座の受講生とどの講座も受講していない生徒
        $studentInOtherCourse = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentInOtherCourse->id, 'course_id' => Course::factory()->create()->id]);
        $studentNoCourse = Student::factory()->create();

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 10,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['student_id' => $studentInCourse->id]);
        $response->assertJsonMissing(['student_id' => $studentInOtherCourse->id]);
        $response->assertJsonMissing(['student_id' => $studentNoCourse->id]);
    }

    /** AC-ANALYTICS-025 */
    public function test_未ログイン受講生が先頭に表示される(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // 30日前にログインした受講生
        $studentOld = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentOld->id, 'course_id' => $course->id]);
        StudentLoginHistory::factory()->create([
            'student_id' => $studentOld->id,
            'logged_in_at' => CarbonImmutable::now()->subDays(30),
        ]);

        // 一度もログインしていない受講生
        $studentNever = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentNever->id, 'course_id' => $course->id]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 10,
        ]));

        // Assert — 未ログイン受講生が先頭
        $response->assertStatus(200);
        $students = $response->json('data');
        $this->assertCount(2, $students);
        $this->assertEquals($studentNever->id, $students[0]['student_id']);
        $this->assertEquals($studentOld->id, $students[1]['student_id']);
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
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 10,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    /**
     * AC-ANALYTICS-024
     *
     * @param  array<string, int>  $query
     */
    #[DataProvider('invalidInputs')]
    public function test_日数の指定または講座が不正だと422になる(bool $courseExists, array $query, string $errorKey): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $courseId = $courseExists
            ? Course::factory()->create(['instructor_id' => $instructor->id])->id
            : 99999;
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $courseId,
            ...$query,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([$errorKey]);
    }

    /** AC-ANALYTICS-027 */
    public function test_公開レッスンをすべて完了した受講生は未完了のチャプターなしで返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $this->actingAs($instructor, 'instructor');

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now()->subDays(5),
        ]);
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => CarbonImmutable::now()->subDays(15),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 10,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'student_id' => $student->id,
            'incomplete_chapter' => null,
        ]);
    }

    /** AC-ANALYTICS-026 */
    public function test_並び順で最初の未完了チャプターを返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1, 'title' => '第1章']);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter1->id, 'order' => 1]);
        $chapter2 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 2, 'title' => '第2章']);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter2->id, 'order' => 1]);
        $chapter3 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 3, 'title' => '第3章']);
        $lesson3 = Lesson::factory()->create(['chapter_id' => $chapter3->id, 'order' => 1]);
        $this->actingAs($instructor, 'instructor');

        // チャプター1のみ完了、チャプター2・3は未完了 → 第2章が返る
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson1->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now()->subDays(10),
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson2->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson3->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => CarbonImmutable::now()->subDays(15),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 10,
        ]));

        // Assert — 並び順で最初の未完了チャプター（第2章）が返る
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'student_id' => $student->id,
            'incomplete_chapter' => [
                'id' => $chapter2->id,
                'title' => '第2章',
            ],
        ]);
    }

    /** AC-ANALYTICS-027 */
    public function test_チャプターがない講座では未完了のチャプターなしで返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        $student = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => CarbonImmutable::now()->subDays(15),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 10,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'student_id' => $student->id,
            'incomplete_chapter' => null,
        ]);
    }

    /** AC-ANALYTICS-027 */
    public function test_未完了なのが下書きと非公開のチャプターだけなら未完了のチャプターなしで返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter1->id, 'order' => 1]);
        $chapter2 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 2, 'status' => StatusEnum::DRAFT->value]);
        Lesson::factory()->create(['chapter_id' => $chapter2->id, 'order' => 1]);
        $chapter3 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 3, 'status' => StatusEnum::PRIVATE->value]);
        Lesson::factory()->create(['chapter_id' => $chapter3->id, 'order' => 1]);
        $this->actingAs($instructor, 'instructor');

        // チャプター1は完了、チャプター2・3は下書き・非公開
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson1->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now()->subDays(5),
        ]);
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => CarbonImmutable::now()->subDays(15),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 10,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'student_id' => $student->id,
            'incomplete_chapter' => null,
        ]);
    }

    /** AC-ANALYTICS-026 */
    #[DataProvider('ineligibleLeadingChapters')]
    public function test_先頭のチャプターが判定の対象外なら後ろの公開チャプターを未完了として返す(string $chapterStatus, string $lessonStatus): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $leadingChapter = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1, 'status' => $chapterStatus]);
        Lesson::factory()->create(['chapter_id' => $leadingChapter->id, 'order' => 1, 'status' => $lessonStatus]);
        $publicChapter = Chapter::factory()->create(['course_id' => $course->id, 'order' => 2, 'title' => '公開チャプター']);
        Lesson::factory()->create(['chapter_id' => $publicChapter->id, 'order' => 1]);
        $this->actingAs($instructor, 'instructor');

        // どのレッスンも未完了のまま
        $student = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => CarbonImmutable::now()->subDays(15),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 10,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'student_id' => $student->id,
            'incomplete_chapter' => [
                'id' => $publicChapter->id,
                'title' => '公開チャプター',
            ],
        ]);
    }

    /** AC-ANALYTICS-026 */
    public function test_公開チャプターに下書きのレッスンが混ざっていても後ろの公開チャプターを未完了として返す(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1, 'title' => '公開チャプター1']);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter1->id, 'order' => 1]);
        Lesson::factory()->create(['chapter_id' => $chapter1->id, 'order' => 2, 'status' => LessonStatusEnum::DRAFT->value]);
        $chapter2 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 2, 'title' => '公開チャプター2']);
        Lesson::factory()->create(['chapter_id' => $chapter2->id, 'order' => 1]);
        $this->actingAs($instructor, 'instructor');

        // 公開チャプター1の公開レッスンは完了済み、同じチャプターの下書きレッスンは完了できない
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson1->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now()->subDays(5),
        ]);
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => CarbonImmutable::now()->subDays(15),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 10,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'student_id' => $student->id,
            'incomplete_chapter' => [
                'id' => $chapter2->id,
                'title' => '公開チャプター2',
            ],
        ]);
    }
}
