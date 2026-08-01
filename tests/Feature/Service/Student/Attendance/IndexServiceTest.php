<?php

namespace Tests\Feature\Service\Student\Attendance;

use App\Dto\Student\Attendance\IndexDto;
use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use App\Services\Student\Attendance\IndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_公開されていないチャプターは進捗の対象に数えない(): void
    {
        // Arrange — 公開1・非公開1のチャプターがあり、公開チャプターは受講済み
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $openChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
        $closedChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PRIVATE->value,
        ]);
        $openLesson = Lesson::factory()->create(['chapter_id' => $openChapter->id]);
        $closedChapterLesson = Lesson::factory()->create(['chapter_id' => $closedChapter->id]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $openLesson->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $closedChapterLesson->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
        ]);

        // Act
        $results = (new IndexService)(new IndexDto($student->id, null), 10, 1, null);

        // Assert — 非公開チャプターが分母に入らないため100%になる
        $this->assertSame(100.0, $results->first()->course->progress_percentage);
    }

    public function test_公開されていないレッスンは受講の完了判定に影響しない(): void
    {
        // Arrange — 公開レッスンは受講済み、下書きレッスンは未着手
        $student = Student::factory()->create();
        $course = Course::factory()->create();
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
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $openLesson->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $draftLesson->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
        ]);

        // Act
        $results = (new IndexService)(new IndexDto($student->id, null), 10, 1, null);

        // Assert
        $this->assertSame(100.0, $results->first()->course->progress_percentage);
    }

    public function test_公開レッスンを持たないチャプターは受講済みとして数えない(): void
    {
        // Arrange — 下書きレッスンしか持たないチャプター
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        // Act
        $results = (new IndexService)(new IndexDto($student->id, null), 10, 1, null);

        // Assert
        $this->assertSame(0.0, $results->first()->course->progress_percentage);
    }
}
