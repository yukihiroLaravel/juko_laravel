<?php

namespace Tests\Feature\Api\Manager\Chapter;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\ManageInstructor;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_チャプター削除_成功(): void
    {
        // Arrange — マネージャー自身の講座のチャプター（受講なし）
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.chapter.delete', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);

        $this->assertSoftDeleted('chapters', [
            'id' => $chapter->id,
        ]);
    }

    public function test_配下の講師チャプター削除_成功(): void
    {
        // Arrange — 配下講師の講座のチャプター
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);
        $course = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.chapter.delete', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'result',
        ]);
        $this->assertSoftDeleted('chapters', [
            'id' => $chapter->id,
        ]);
    }

    public function test_マネージャーの受講済みレッスン削除_失敗(): void
    {
        // Arrange — チャプター内のレッスンに受講中のレッスン受講がある
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
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
            'status' => LessonAttendance::STATUS_IN_ATTENDANCE,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.chapter.delete', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, this lesson has attendance.',
        ]);
    }

    public function test_配下でない講師_失敗(): void
    {
        // Arrange — 別のマネージャーの講座のチャプターを削除しようとする
        $otherManager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherManager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.chapter.delete', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to delete this chapter.',
        ]);
    }

    public function test_マネージャーではない講師_失敗(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $nonManager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.chapter.delete', [
            'course_id' => $course->id,
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_バリデーションエラー(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.chapter.delete', [
            'course_id' => 'aaa',
            'chapter_id' => 'bbb',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'chapter_id',
        ]);
    }
}
