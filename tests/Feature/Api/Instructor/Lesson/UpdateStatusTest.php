<?php

namespace Tests\Feature\Api\Instructor\Lesson;

use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\Chapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_バリデーションエラー(): void
    {
        $response = $this->patchJson(route('instructor.lessons.update-status', ['lesson_id' => 999]), [
            'status' => 'invalid',
        ]);
        $response->assertStatus(422);
    }

    public function test_ステータスをdraftからpublicに変更して受講状況が生成される(): void
    {
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => 'draft']);
        $this->actingAs($instructor, 'instructor');

        $response = $this->patchJson(route('instructor.lessons.update-status', ['lesson_id' => $lesson->id]), [
            'status' => 'public',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('lesson_attendances', [
            'lesson_id' => $lesson->id,
        ]);
    }

    public function test_禁止されたステータス変更は失敗する(): void
    {
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => 'public']);
        $this->actingAs($instructor, 'instructor');

        $response = $this->patchJson(route('instructor.lessons.update-status', ['lesson_id' => $lesson->id]), [
            'status' => 'private',
        ]);

        $response->assertStatus(422);
    }
}