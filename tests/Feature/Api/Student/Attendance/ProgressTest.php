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

    public function test_受講生に公開されていないレッスンは進捗の対象にならない(): void
    {
        // Arrange — 公開中・非公開・下書きのレッスンをそれぞれ1件ずつ持つチャプター
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = collect([
            LessonStatusEnum::PUBLIC,
            LessonStatusEnum::PRIVATE,
            LessonStatusEnum::DRAFT,
        ])->map(fn (LessonStatusEnum $status) => Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => $status->value,
        ]));
        $lessons->each(fn (Lesson $lesson) => LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
        ]));
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert — 公開中の1件だけが総数に数えられる
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_total_lessons', 1);
        $response->assertJsonPath('data.number_of_total_chapters', 1);
    }

    public function test_公開されていないレッスンを終えていても完了数に数えない(): void
    {
        // Arrange — 非公開のレッスンだけを完了している状態
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $privateLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $publicLesson->id,
        ]);
        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $privateLesson->id,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert — 非公開のレッスンは分母にも分子にも含まれない
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_total_lessons', 1);
        $response->assertJsonPath('data.number_of_completed_lessons', 0);
        $response->assertJsonPath('data.number_of_completed_chapters', 0);
    }

    public function test_受講生に公開されていないチャプターは進捗の対象にならない(): void
    {
        // Arrange — 公開中・非公開・下書きのチャプターにそれぞれ公開中のレッスンがある
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        collect([
            ChapterStatusEnum::PUBLIC,
            ChapterStatusEnum::PRIVATE,
            ChapterStatusEnum::DRAFT,
        ])->each(function (ChapterStatusEnum $status) use ($course, $attendance) {
            $chapter = Chapter::factory()->create([
                'course_id' => $course->id,
                'status' => $status->value,
            ]);
            $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
            LessonAttendance::factory()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
            ]);
        });
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert — 公開中のチャプターとそのレッスンだけが総数に数えられる
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_total_chapters', 1);
        $response->assertJsonPath('data.number_of_total_lessons', 1);
    }

    public function test_公開中のレッスンがないチャプターは進捗の対象にならない(): void
    {
        // Arrange — 公開中のレッスンを持つチャプターと、下書きのレッスンしかないチャプター
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapterWithPublicLesson = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $chapterWithPublicLesson->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $chapterWithDraftLessonOnly = Chapter::factory()->create(['course_id' => $course->id, 'order' => 2]);
        $draftLesson = Lesson::factory()->create([
            'chapter_id' => $chapterWithDraftLessonOnly->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);
        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $publicLesson->id,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $draftLesson->id,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert — 到達できないチャプターを除いた結果、全チャプターが完了になる
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_total_chapters', 1);
        $response->assertJsonPath('data.number_of_completed_chapters', 1);
        $response->assertJsonPath('data.continue_from', null);
    }

    public function test_一度終えたレッスンは受講中に戻しても完了として数え続ける(): void
    {
        // Arrange — 完了日時が記録されたあとステータスだけ受講中に戻したレッスン
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $lessonAttendance = LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
        ]);
        $lessonAttendance->changeStatus(LessonAttendance::STATUS_IN_ATTENDANCE);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.progress', ['attendance_id' => $attendance->id]));

        // Assert — 終えた事実は変わらないため完了として扱われる
        $response->assertStatus(200);
        $response->assertJsonPath('data.number_of_completed_lessons', 1);
        $response->assertJsonPath('data.number_of_completed_chapters', 1);
        $response->assertJsonPath('data.continue_from', null);
    }
}
