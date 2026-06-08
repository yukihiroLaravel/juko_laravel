<?php

namespace Tests\Feature\Service\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use App\Model\Course;
use App\Services\Chapter\StatusTransitionService;
use App\Services\Chapter\UpdateAllChaptersStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateAllChaptersStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private UpdateAllChaptersStatusService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UpdateAllChaptersStatusService(new StatusTransitionService);
    }

    public function test_講座内の全チャプターを公開にできる(): void
    {
        // Arrange（下書き・非公開いずれからも公開への遷移は許容される）
        $course = Course::factory()->create();
        $draftChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => StatusEnum::DRAFT->value]);
        $privateChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => StatusEnum::PRIVATE->value]);

        // Act
        ($this->service)($course->id, StatusEnum::PUBLIC->value);

        // Assert
        $this->assertDatabaseHas('chapters', ['id' => $draftChapter->id, 'status' => StatusEnum::PUBLIC->value]);
        $this->assertDatabaseHas('chapters', ['id' => $privateChapter->id, 'status' => StatusEnum::PUBLIC->value]);
    }

    public function test_講座内の全チャプターを非公開にできる(): void
    {
        // Arrange（公開・非公開いずれからも非公開への遷移は許容される）
        $course = Course::factory()->create();
        $publicChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => StatusEnum::PUBLIC->value]);
        $privateChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => StatusEnum::PRIVATE->value]);

        // Act
        ($this->service)($course->id, StatusEnum::PRIVATE->value);

        // Assert
        $this->assertDatabaseHas('chapters', ['id' => $publicChapter->id, 'status' => StatusEnum::PRIVATE->value]);
        $this->assertDatabaseHas('chapters', ['id' => $privateChapter->id, 'status' => StatusEnum::PRIVATE->value]);
    }

    public function test_下書きを含む講座を非公開にしようとすると例外を発生させ全件更新しない(): void
    {
        // Arrange（下書き→非公開は許容されないため、講座全体の更新が失敗する）
        $course = Course::factory()->create();
        $publicChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => StatusEnum::PUBLIC->value]);
        $draftChapter = Chapter::factory()->create(['course_id' => $course->id, 'status' => StatusEnum::DRAFT->value]);

        // Act & Assert
        try {
            ($this->service)($course->id, StatusEnum::PRIVATE->value);
            $this->fail('ValidationExceptionが発生しませんでした。');
        } catch (ValidationException) {
            // 検証段階で中断するため、更新は一切行われない
        }

        // Assert（原子性: いずれのチャプターも元のステータスのまま）
        $this->assertDatabaseHas('chapters', ['id' => $publicChapter->id, 'status' => StatusEnum::PUBLIC->value]);
        $this->assertDatabaseHas('chapters', ['id' => $draftChapter->id, 'status' => StatusEnum::DRAFT->value]);
    }
}
