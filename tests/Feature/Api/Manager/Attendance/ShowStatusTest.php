<?php

namespace Tests\Feature\Api\Manager\Attendance;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_当日の出席状況を取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_今月の出席状況を取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_無効のパラメータ(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => 9999,
            'period' => 'invalid',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'period',
        ]);
    }

    public function test_配下ではない講師の講座_失敗(): void
    {
        // Arrange — 別のマネージャーの講座
        $otherManager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherManager->id]);
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_マネージャーではない講師_失敗(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $nonManager->id]);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(403);
    }
    
    public function test_公開レッスンのみ完了していればチャプター完了としてカウントされる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();

        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);

        // 公開レッスン
        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
        ]);

        // 下書きレッスン
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);

        // 非公開レッスン
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        // 公開レッスンだけ完了
        LessonAttendance::factory()
            ->completed()
            ->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $publicLesson->id,
            ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 1,
        ]);
    }

    public function test_下書きチャプターは完了チャプター数に含まれない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();

        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::DRAFT->value,
        ]);

        $lesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()
            ->completed()
            ->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
            ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 0,
        ]);
    }

    public function test_公開レッスンが0件のチャプターは完了チャプター数に含まれない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();

        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);

        // 下書きレッスンのみ作成
        $draftLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        // 下書きレッスンを完了済みにする
        LessonAttendance::factory()
            ->completed()
            ->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $draftLesson->id,
            ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 0,
        ]);
    }
}
