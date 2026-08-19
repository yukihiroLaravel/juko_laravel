<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\ManageInstructor;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_マネージャーで受講状況を取得(): void
    {
        // Arrange — マネージャーが自分の講座の受講を取得
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($manager, 'instructor');

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
    }

    public function test_マネージャーで配下の講師で受講状況を取得(): void
    {
        // Arrange — マネージャーが配下講師の講座の受講を取得
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($manager, 'instructor');

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
    }

    public function test_講師で受講状況を取得(): void
    {
        // Arrange — 講師が自分の講座の受講を取得
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
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
    }

    public function test_レスポンスの値が正しい(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 30,
        ]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'attendance_id' => $attendance->id,
                'course' => [
                    'course_id' => $course->id,
                    'title' => $course->title,
                    'status' => $course->status->value,
                    'capacity' => 30,
                ],
                'students_count' => 1,
            ],
        ]);
    }

    public function test_定員未設定の場合capacityがnullで返る(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => null,
        ]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'course' => [
                    'capacity' => null,
                ],
            ],
        ]);
    }

    public function test_受講生が複数いる場合のカウント(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $students = Student::factory()->count(3)->create();
        foreach ($students as $student) {
            Attendance::factory()->create([
                'student_id' => $student->id,
                'course_id' => $course->id,
            ]);
        }
        $firstAttendance = Attendance::where('course_id', $course->id)->first();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $firstAttendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'students_count' => 3,
            ],
        ]);
    }

    public function test_受講期限切れの場合は閲覧不可(): void
    {
        // Arrange — 受講期限が過去の受講
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::yesterday(),
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_権限がない講師_失敗(): void
    {
        // Arrange — 他の講師の講座の受講を取得しようとする
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_マネージャーで配下でない講師の受講は取得不可(): void
    {
        // Arrange — マネージャーだが配下でない講師の講座
        $manager = Instructor::factory()->create();
        $unrelatedInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $unrelatedInstructor->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_論理削除済みの受講はバリデーションエラー(): void
    {
        // Arrange — 論理削除された受講
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $attendanceId = $attendance->id;
        $attendance->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendanceId]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attendance_id']);
    }

    public function test_存在しないattendance_idはバリデーションエラー(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => 99999]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['attendance_id']);
    }

    public function test_バリデーションエラー_文字列(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'attendance_id',
        ]);
    }

    public function test_講座に期限設定がある場合のレスポンス(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'deadline_type' => 'fixed',
        ]);
        CourseDeadline::factory()->create([
            'course_id' => $course->id,
            'fixed_date' => '2026-12-31',
        ]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'course' => [
                    'deadline_type' => 'fixed',
                ],
            ],
        ]);
        $response->assertJsonPath('data.course.course_deadline.fixed_date', '2026-12-31');
    }

    public function test_期限切れの受講者は受講人数から除外される(): void
    {
        // Arrange — 期限内の受講者と期限切れの受講者を作成し、期限内の受講で取得
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $activeStudent = Student::factory()->create();
        $activeAttendance = Attendance::factory()->create([
            'student_id' => $activeStudent->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::tomorrow(),
        ]);
        $expiredStudent = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $expiredStudent->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::yesterday(),
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $activeAttendance->id]));

        // Assert — 期限切れの受講者は除外されてカウントされる
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'students_count' => 1,
            ],
        ]);
    }

    public function test_当日が期限の受講者は受講人数に含まれる(): void
    {
        // Arrange — 当日が期限の受講者と期限内の受講者を作成
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $todayDeadlineStudent = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $todayDeadlineStudent->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::today(),
        ]);
        $futureDeadlineStudent = Student::factory()->create();
        $futureAttendance = Attendance::factory()->create([
            'student_id' => $futureDeadlineStudent->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::tomorrow(),
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $futureAttendance->id]));

        // Assert — 当日が期限の受講者も含めてカウントされる（>= の境界）
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'students_count' => 2,
            ],
        ]);
    }

    public function test_受講期限なしの受講者は受講人数に含まれる(): void
    {
        // Arrange — 期限なしの受講者と期限内の受講者を作成
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $noDeadlineStudent = Student::factory()->create();
        Attendance::factory()->create([
            'student_id' => $noDeadlineStudent->id,
            'course_id' => $course->id,
            'attendance_deadline' => null,
        ]);
        $activeStudent = Student::factory()->create();
        $activeAttendance = Attendance::factory()->create([
            'student_id' => $activeStudent->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::tomorrow(),
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $activeAttendance->id]));

        // Assert — 期限なしの受講者も含めてカウントされる
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'students_count' => 2,
            ],
        ]);
    }

    public function test_チャプター情報がレスポンスに含まれる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert — chapters キー配下に各チャプター情報と完了人数が含まれる
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

    public function test_チャプター内の全レッスンを完了した受講者はチャプター完了人数にカウントされる(): void
    {
        // Arrange — 2レッスン両方を完了した受講者を作成
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson1->id,
            'attendance_id' => $attendance->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now(),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson2->id,
            'attendance_id' => $attendance->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now(),
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters.0.chapter_id', $chapter->id);
        $response->assertJsonPath('data.chapters.0.completed_students_count', 1);
    }

    public function test_チャプター内に未完了レッスンが残っている受講者はチャプター完了人数にカウントされない(): void
    {
        // Arrange — 2レッスン中1レッスンのみ完了した受講者を作成
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson1->id,
            'attendance_id' => $attendance->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now(),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson2->id,
            'attendance_id' => $attendance->id,
            'completed_at' => null,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters.0.completed_students_count', 0);
    }

    public function test_期限切れの受講者は全レッスン完了でもチャプター完了人数から除外される(): void
    {
        // Arrange — 期限切れの受講者と期限内の受講者の両方が全レッスン完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);

        $activeStudent = Student::factory()->create();
        $activeAttendance = Attendance::factory()->create([
            'student_id' => $activeStudent->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::tomorrow(),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson->id,
            'attendance_id' => $activeAttendance->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now(),
        ]);

        $expiredStudent = Student::factory()->create();
        $expiredAttendance = Attendance::factory()->create([
            'student_id' => $expiredStudent->id,
            'course_id' => $course->id,
            'attendance_deadline' => CarbonImmutable::yesterday(),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson->id,
            'attendance_id' => $expiredAttendance->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now(),
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $activeAttendance->id]));

        // Assert — 期限切れの受講者は除外され、期限内の1名のみカウントされる
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters.0.completed_students_count', 1);
    }

    public function test_レッスンを持たないチャプターのチャプター完了人数は0になる(): void
    {
        // Arrange — レッスンが存在しないチャプターを作成
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert — レッスン0個のチャプターは判定対象がないため0が返る
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters.0.chapter_id', $chapter->id);
        $response->assertJsonPath('data.chapters.0.completed_students_count', 0);
    }

    public function test_複数チャプターそれぞれのチャプター完了人数が個別に返る(): void
    {
        // Arrange — 2チャプター存在、受講者はチャプター1のみ全レッスン完了
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        $chapter2 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 2]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter1->id]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter2->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson1->id,
            'attendance_id' => $attendance->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => CarbonImmutable::now(),
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson2->id,
            'attendance_id' => $attendance->id,
            'completed_at' => null,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert — chapter1 は全レッスン完了で1、chapter2 は未完了で0
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters.0.chapter_id', $chapter1->id);
        $response->assertJsonPath('data.chapters.0.completed_students_count', 1);
        $response->assertJsonPath('data.chapters.1.chapter_id', $chapter2->id);
        $response->assertJsonPath('data.chapters.1.completed_students_count', 0);
    }

    public function test_完了日時が未設定の場合は表示用ステータスが完了でもチャプター完了人数にカウントされない(): void
    {
        // Arrange — 表示用ステータスは完了だが completed_at（完了日時）が未設定の状態
        $instructor = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson->id,
            'attendance_id' => $attendance->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => null,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert — completed_at が null なら status に関わらずカウントされない
        $response->assertStatus(200);
        $response->assertJsonPath('data.chapters.0.completed_students_count', 0);
    }
}
