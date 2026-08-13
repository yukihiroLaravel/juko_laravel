<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Course\StatusEnum as CourseStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteAllChaptersTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_全チャプター完了_成功(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-chapters', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'status' => 'completed_attendance',
        ]);
    }

    public function test_全チャプター完了で完了日時が記録される(): void
    {
        // Arrange — 未着手のレッスンと、過去に完了済みのレッスン
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-01 10:00:00'));
        $completedAt = CarbonImmutable::parse('2026-07-01 09:00:00');
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $newLesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $completedLesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $newLesson->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $completedLesson->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => $completedAt,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-chapters', ['attendance_id' => $attendance->id]));

        // Assert — 未着手だったレッスンには現在時刻が記録され、既に完了していたレッスンの日時は変わらない
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $newLesson->id,
            'completed_at' => CarbonImmutable::now(),
        ]);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $completedLesson->id,
            'completed_at' => $completedAt,
        ]);
    }

    public function test_公開されていないチャプターとレッスンは完了にならない(): void
    {
        // Arrange — 公開チャプター（公開レッスン1・下書きレッスン1）と非公開チャプター（レッスン1）
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $openChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
        $closedChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PRIVATE->value,
        ]);
        $openLesson = Lesson::factory()->create([
            'chapter_id' => $openChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $draftLesson = Lesson::factory()->create([
            'chapter_id' => $openChapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);
        $closedChapterLesson = Lesson::factory()->create([
            'chapter_id' => $closedChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        foreach ([$openLesson, $draftLesson, $closedChapterLesson] as $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
            ]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-chapters', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $openLesson->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
        ]);
        foreach ([$draftLesson, $closedChapterLesson] as $lesson) {
            $this->assertDatabaseHas('lesson_attendances', [
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
            ]);
        }
    }

    public function test_公開されていない講座は完了にできない(): void
    {
        // Arrange — 受講中の講座が非公開になっている
        $student = Student::factory()->create();
        $course = Course::factory()->create(['status' => CourseStatusEnum::PRIVATE->value]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
        $lesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-chapters', ['attendance_id' => $attendance->id]));

        // Assert — 受講生に見えない講座は完了操作を受け付けない
        $response->assertStatus(403);
        $this->assertDatabaseHas('lesson_attendances', [
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
            'completed_at' => null,
        ]);
    }

    public function test_権限がない生徒_失敗(): void
    {
        // Arrange — 別の生徒のAttendanceにアクセス
        $owner = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $owner->id,
            'course_id' => $course->id,
        ]);
        $unauthorizedStudent = Student::factory()->create();
        $this->actingAs($unauthorizedStudent);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-chapters', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->putJson(route('student.attendances.complete-all-chapters', ['attendance_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'attendance_id',
        ]);
    }
}
