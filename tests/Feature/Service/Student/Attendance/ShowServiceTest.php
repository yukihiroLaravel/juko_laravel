<?php

namespace Tests\Feature\Service\Student\Attendance;

use App\Dto\Student\Attendance\ShowDto;
use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\Student;
use App\Services\Student\Attendance\ShowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講している講座の詳細情報を取得できる(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $showDto = new ShowDto($attendance->id);
        $service = new ShowService;

        // Act
        $result = $service($showDto);

        // Assert
        $this->assertInstanceOf(Attendance::class, $result);
        $this->assertEquals($attendance->id, $result->id);
        $this->assertNotNull($result->course);
        $this->assertInstanceOf(Course::class, $result->course);
    }

    public function test_公開されていないチャプターとレッスンは詳細情報に含まれない(): void
    {
        // Arrange
        $course = Course::factory()->create();
        $publicChapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);
        Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PRIVATE->value,
        ]);
        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $publicChapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $draftLesson = Lesson::factory()->create([
            'chapter_id' => $publicChapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $showDto = new ShowDto($attendance->id);
        $service = new ShowService;

        // Act
        $result = $service($showDto);

        // Assert
        $this->assertSame(
            [$publicChapter->id],
            $result->course->publicChapters->pluck('id')->all()
        );
        $this->assertSame(
            [$publicLesson->id],
            $result->course->publicChapters->first()->publicLessons->pluck('id')->all()
        );
        $this->assertNotContains(
            $draftLesson->id,
            $result->course->publicChapters->first()->publicLessons->pluck('id')->all()
        );
    }
}
