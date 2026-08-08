<?php

namespace Tests\Feature\Api\Student\Attendance;

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

    public function test_受講詳細に公開されていないレッスンは含まれない(): void
    {
        // Arrange — 公開中・非公開・下書きのレッスンをそれぞれ1件ずつ持つチャプター
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 1,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $privateLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 2,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);
        // 下書きのレッスンには受講状況が作られない
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'order' => 3,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);
        foreach ([$publicLesson, $privateLesson] as $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
            ]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.show', ['attendance_id' => $attendance->id]));

        // Assert — 公開中のレッスンだけが返る
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.course.chapters.0.lessons');
        $response->assertJsonPath('data.course.chapters.0.lessons.0.lesson_id', $publicLesson->id);
    }
}
