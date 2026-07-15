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
        /** @var Instructor $instructor */
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => 'draft']);
        // ログイン処理を追加
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
        /** @var Instructor $instructor */
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

    /** @test */
    public function 同一ステータス時の属性更新では受講状況は増加しない()
    {
        // 1. 準備：publicなレッスンを用意
        /** @var Instructor $instructor */
        $instructor = Instructor::factory()->createOne();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id, 'status' => 'public']);
        $this->actingAs($instructor, 'instructor');

        // 2. 実行：同じステータスで属性のみ更新
        $response = $this->patchJson(route('instructor.lessons.update-status', ['lesson_id' => $lesson->id]), [
            'status' => 'public',
            'title' => '更新後のタイトル'
        ]);

        // 3. 検証：200であること
        $response->assertStatus(200);
        
        // 4. 検証：受講状況レコードが増えていないこと
        $this->assertDatabaseCount('lesson_attendances', 0); // もし他にレコードがない場合
        // または、特定のレッスンIDで件数が変化していないことを検証
        $this->assertEquals('更新後のタイトル', $lesson->fresh()->title);
    }
}