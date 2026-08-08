<?php

namespace Tests\Feature\Service\Student\Attendance;

use App\Dto\Student\Attendance\IndexDto;
use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\Student;
use App\Services\Student\Attendance\IndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講一覧では公開中のチャプターとレッスンだけを読み込む(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $openChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
        Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PRIVATE->value,
        ]);
        $openLesson = Lesson::factory()->create([
            'chapter_id' => $openChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $draftLesson = Lesson::factory()->create([
            'chapter_id' => $openChapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        // Act
        $results = (new IndexService)(new IndexDto($student->id, null), 10, 1, null);

        // Assert
        $chapters = $results->first()->course->publicChapters;
        $this->assertSame([$openChapter->id], $chapters->pluck('id')->all());
        $this->assertSame([$openLesson->id], $chapters->first()->publicLessons->pluck('id')->all());
        $this->assertNotContains($draftLesson->id, $chapters->first()->publicLessons->pluck('id')->all());
    }
}
