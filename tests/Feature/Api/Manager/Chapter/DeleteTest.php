<?php

namespace Tests\Feature\Api\Manager\Chapter;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\ManageInstructor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use RefreshDatabase;

    /** AC-AUTHOR-024 */
    public function test_チャプターを削除すると配下のレッスンも削除される(): void
    {
        // Arrange — マネージャー自身の講座のチャプター（受講なし）
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.chapters.delete', [
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
        $this->assertSoftDeleted('lessons', [
            'id' => $lesson->id,
        ]);
    }

    /** AC-AUTHZ-002 */
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
        $response = $this->deleteJson(route('manager.chapters.delete', [
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

    /** AC-AUTHOR-023 */
    public function test_受講状況があるレッスンを含むチャプターは削除できない(): void
    {
        // Arrange — チャプター内のレッスンに受講状況がある
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $attendance = Attendance::factory()->create(['course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'lesson_id' => $lesson->id,
            'attendance_id' => $attendance->id,
        ]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.chapters.delete', [
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, this chapter has lessons with attendance.',
        ]);
    }

    /** AC-AUTHZ-003 */
    public function test_配下でないマネージャーのチャプターは削除できない(): void
    {
        // Arrange — 別のマネージャーの講座のチャプターを削除しようとする
        $otherManager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherManager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.chapters.delete', [
            'chapter_id' => $chapter->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_マネージャーではない講師_失敗(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.chapters.delete', [
            'chapter_id' => 1,
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
        $response = $this->deleteJson(route('manager.chapters.delete', [
            'chapter_id' => 'bbb',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'chapter_id',
        ]);
    }

    /** AC-COMMON-015 */
    public function test_削除の途中で失敗するとレッスンも元のまま残る(): void
    {
        // Arrange — レッスンを消したあと、チャプターを消すクエリの直後に失敗させる
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->failAfterQuery('update "chapters"');
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->deleteJson(route('manager.chapters.delete', ['chapter_id' => $chapter->id]));

        // Assert
        $response->assertStatus(500);
        $this->assertNotSoftDeleted('chapters', ['id' => $chapter->id]);
        $this->assertNotSoftDeleted('lessons', ['id' => $lesson->id]);
    }
}
