<?php

namespace Tests\Feature\Api\Instructor\Attendance;

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
use Tests\TestCase;

class FollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_10日以上ログインがない受講生を取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1, 'title' => 'チャプター1']);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter1->id, 'order' => 1]);
        $chapter2 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 2, 'title' => 'チャプター2']);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter2->id, 'order' => 1]);
        $this->actingAs($instructor, 'instructor');

        // 15日前にログインした受講生（対象）— チャプター1完了、チャプター2未完了
        $studentOld = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $studentOld->id, 'course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson1->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now()->subDays(20),
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson2->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
        ]);
        StudentLoginHistory::factory()->create([
            'student_id' => $studentOld->id,
            'logged_in_at' => CarbonImmutable::now()->subDays(15),
        ]);

        // 5日前にログインした受講生（対象外）
        $studentRecent = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentRecent->id, 'course_id' => $course->id]);
        StudentLoginHistory::factory()->create([
            'student_id' => $studentRecent->id,
            'logged_in_at' => CarbonImmutable::now()->subDays(5),
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
            'student_id' => $studentOld->id,
            'incomplete_chapter' => [
                'id' => $chapter2->id,
                'title' => 'チャプター2',
            ],
        ]);
        $response->assertJsonMissing(['student_id' => $studentRecent->id]);
    }

    public function test_一度もログインがない受講生が含まれる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
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

    public function test_他講座の受講生は含まれない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $otherCourse = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // 自分の講座の受講生（対象）
        $studentInCourse = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentInCourse->id, 'course_id' => $course->id]);
        StudentLoginHistory::factory()->create([
            'student_id' => $studentInCourse->id,
            'logged_in_at' => CarbonImmutable::now()->subDays(20),
        ]);

        // 別の講座の受講生（対象外）— ログイン履歴なし
        $studentInOtherCourse = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentInOtherCourse->id, 'course_id' => $otherCourse->id]);

        // どの講座にも受講していない生徒（対象外）— ログイン履歴なし
        $studentNoCourse = Student::factory()->create();

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 10,
        ]));

        // Assert — 自分の講座の受講生のみ返る
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['student_id' => $studentInCourse->id]);
        $response->assertJsonMissing(['student_id' => $studentInOtherCourse->id]);
        $response->assertJsonMissing(['student_id' => $studentNoCourse->id]);
    }

    public function test_一度もログインがない他講座の受講生は含まれない(): void
    {
        // Arrange
        // ※ 現行実装の orWhereNotExists バグを検出するテスト
        // orWhereNotExists が OR 条件のため、whereHas の講座絞り込みが無効化され
        // ログイン履歴のない全生徒が返ってしまう問題
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // 対象講座にログイン履歴なしの受講生（対象）
        $studentInCourse = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentInCourse->id, 'course_id' => $course->id]);

        // 別講座にログイン履歴なしの受講生（対象外）
        $otherCourse = Course::factory()->create();
        $studentInOtherCourse = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentInOtherCourse->id, 'course_id' => $otherCourse->id]);

        // どの講座にも属さずログイン履歴なしの受講生（対象外）
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
        // ↓ 現行実装では orWhereNotExists のバグにより、これらが含まれてしまう可能性がある
        $response->assertJsonMissing(['student_id' => $studentInOtherCourse->id]);
        $response->assertJsonMissing(['student_id' => $studentNoCourse->id]);
    }

    public function test_180日以上ログインがない受講生を取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // 200日前にログインした受講生（対象）
        $studentOld = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentOld->id, 'course_id' => $course->id]);
        StudentLoginHistory::factory()->create([
            'student_id' => $studentOld->id,
            'logged_in_at' => CarbonImmutable::now()->subDays(200),
        ]);

        // 100日前にログインした受講生（対象外）
        $studentMid = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $studentMid->id, 'course_id' => $course->id]);
        StudentLoginHistory::factory()->create([
            'student_id' => $studentMid->id,
            'logged_in_at' => CarbonImmutable::now()->subDays(100),
        ]);

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 180,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['student_id' => $studentOld->id]);
        $response->assertJsonMissing(['student_id' => $studentMid->id]);
    }

    public function test_未ログイン受講生が先頭に表示される(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
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

    public function test_権限のない講師_失敗(): void
    {
        // Arrange
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 10,
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_バリデーションエラー_daysが未指定(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['days']);
    }

    public function test_バリデーションエラー_daysが0以下(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => $course->id,
            'days' => 0,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['days']);
    }

    public function test_バリデーションエラー_存在しない講座_id(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.follow-up', [
            'course_id' => 99999,
            'days' => 10,
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['course_id']);
    }

    public function test_全レッスン完了済みの場合_incomplete_chapterがnull(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $this->actingAs($instructor, 'instructor');

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
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

    public function test_並び順で最初の未完了チャプターが返る(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
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
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now()->subDays(10),
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson2->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson3->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
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

    public function test_チャプターがない講座の場合_incomplete_chapterがnull(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
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
}
