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

    public function test_全チャプターの公開状態を更新_成功(): void
    {
        // Arrange
        $service = new UpdateAllChaptersStatusService;
        $statusTransitionService = new StatusTransitionService;

        $course = Course::factory()->create();
        $chapters = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => StatusEnum::PUBLIC->value,
        ]);

        // Act
        $service($course->id, 'private', $statusTransitionService);

        // Assert
        $this->assertDatabaseHas('chapters', [
            'id' => $chapters->id,
            'status' => StatusEnum::PRIVATE->value,
        ]);
    }
}