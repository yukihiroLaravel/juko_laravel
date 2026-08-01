<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講進捗を取得_成功(): void
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
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_完了日時がなければ表示用ステータスが完了でも受講済みにならない(): void
    {
        // Arrange — 表示用ステータスは完了だが、完了日時が記録されていない状態
        $student = Student::factory()->create();
        $course = Course::factory()->create();
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
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
            'completed_at' => null,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert — 完了日時が判定の正なので、受講済みとして数えない
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_completed_lessons', 0);
        $response->assertJsonPath('data.number_of_completed_chapters', 0);
        $response->assertJsonPath('data.continue_from.lesson_id', $lesson->id);
    }

    public function test_公開されていないチャプターとレッスンは進捗の件数に含まれない(): void
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
            LessonAttendance::factory()->completed()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
            ]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert — 公開チャプター1つ・公開レッスン1つだけが件数に入る
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_total_chapters', 1);
        $response->assertJsonPath('data.number_of_completed_chapters', 1);
        $response->assertJsonPath('data.number_of_total_lessons', 1);
        $response->assertJsonPath('data.number_of_completed_lessons', 1);
    }

    public function test_公開レッスンをすべて受講すると進捗が完了になる(): void
    {
        // Arrange — 公開レッスンのみ受講済みで、下書きレッスンは未着手のまま
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
        $openLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $draftLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);
        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $openLesson->id,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $draftLesson->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_completed_chapters', 1);
        $response->assertJsonPath('data.number_of_total_chapters', 1);
        $response->assertJsonPath('data.number_of_completed_lessons', 1);
        $response->assertJsonPath('data.number_of_total_lessons', 1);
        $response->assertJsonPath('data.continue_from', null);
    }

    public function test_受講進捗を取得_他の生徒の進捗を取得_失敗(): void
    {
        // Arrange — 別の生徒のAttendanceにアクセス
        $owner = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $owner->id,
            'course_id' => $course->id,
        ]);
        $otherStudent = Student::factory()->create();
        $this->actingAs($otherStudent);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => 'abc']));

        // Assert
        $response->assertStatus(422);
    }

    public function test_受講期限切れの受講進捗を取得_失敗(): void
    {
        // Arrange — 期限切れのAttendance
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => now()->subDays(1),
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_受講期限当日は受講進捗を取得_成功(): void
    {
        // Arrange — 期限が当日のAttendance
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'attendance_deadline' => now(),
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
    }
}
