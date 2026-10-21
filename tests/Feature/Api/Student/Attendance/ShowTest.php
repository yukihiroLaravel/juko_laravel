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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_自分の受講の詳細を取得できる(): void
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

    /** AC-AUTHZ-006 */
    public function test_他の受講生の受講詳細は閲覧できない(): void
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

    /** AC-AUTHZ-007 */
    #[DataProvider('unpublishedCourseStatusProvider')]
    public function test_公開されていない講座の受講詳細は閲覧できない(CourseStatusEnum $courseStatus): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create(['status' => $courseStatus->value]);
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

    /** @return array<string, array{CourseStatusEnum}> */
    public static function unpublishedCourseStatusProvider(): array
    {
        return [
            '公開が取り消された講座' => [CourseStatusEnum::PRIVATE],
            '下書きの講座' => [CourseStatusEnum::DRAFT],
        ];
    }

    /** AC-ENROLL-029 */
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
        Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PRIVATE->value,
            'order' => 2,
        ]);
        $openLesson = Lesson::factory()->create([
            'chapter_id' => $openChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        Lesson::factory()->create([
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
    }

    public function test_受講idが数字でない場合は入力エラーになる(): void
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
