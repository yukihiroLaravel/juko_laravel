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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講取得_成功(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_権限がない受講生_失敗(): void
    {
        // Arrange — 別の受講生のAttendanceにアクセス
        $owner = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $owner->id,
            'course_id' => $course->id,
        ]);
        $unauthorizedStudent = Student::factory()->create();
        $this->actingAs($unauthorizedStudent);

        // Act
        $response = $this->getJson(route('student.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_公開が取り消された講座の受講詳細は閲覧できない(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create(['status' => CourseStatusEnum::PRIVATE->value]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_下書きの講座の受講詳細は閲覧できない(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create(['status' => CourseStatusEnum::DRAFT->value]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_受講詳細には公開中のチャプターとレッスンだけが並ぶ(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $openChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
            'order' => 1,
        ]);
        $closedChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PRIVATE->value,
            'order' => 2,
        ]);
        $openLesson = Lesson::factory()->create([
            'chapter_id' => $openChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $draftLesson = Lesson::factory()->create([
            'chapter_id' => $openChapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);
        $closedLesson = Lesson::factory()->create([
            'chapter_id' => $openChapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        // 下書き以外のレッスンには受講状況が作成される
        foreach ([$openLesson, $closedLesson] as $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
            ]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert
        $response->assertStatus(200);
        $chapters = $response->json('data.course.chapters');
        $this->assertSame([$openChapter->id], array_column($chapters, 'chapter_id'));
        $this->assertSame([$openLesson->id], array_column($chapters[0]['lessons'], 'lesson_id'));
        $this->assertNotContains($draftLesson->id, array_column($chapters[0]['lessons'], 'lesson_id'));
        $this->assertNotContains($closedLesson->id, array_column($chapters[0]['lessons'], 'lesson_id'));
        $this->assertNotContains($closedChapter->id, array_column($chapters, 'chapter_id'));
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.show', ['attendance_id' => 'aaa']));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'attendance_id',
        ]);
    }
}
