<?php

namespace Tests\Feature\Service\Course;

use App\Enums\Course\StatusEnum;
use App\Model\Course;
use App\Services\Course\PutStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PutStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function allowedStatusProvider(): array
    {
        return [
            [StatusEnum::DRAFT->value, StatusEnum::PUBLIC->value],
            [StatusEnum::PUBLIC->value, StatusEnum::PRIVATE->value],
            [StatusEnum::PRIVATE->value, StatusEnum::PUBLIC->value],
            [StatusEnum::PUBLIC->value, StatusEnum::PUBLIC->value],
            [StatusEnum::PRIVATE->value, StatusEnum::PRIVATE->value],
        ];
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public static function disallowedStatusProvider(): array
    {
        return [
            [StatusEnum::DRAFT->value, StatusEnum::DRAFT->value],
            [StatusEnum::DRAFT->value, StatusEnum::PRIVATE->value],
            [StatusEnum::PUBLIC->value, StatusEnum::DRAFT->value],
            [StatusEnum::PRIVATE->value, StatusEnum::DRAFT->value],
        ];
    }

    #[DataProvider('allowedStatusProvider')]
    public function test_講座ステータス一括更新_許容する組み合わせ_成功(string $currentStatus, string $targetStatus): void
    {
        // Arrange
        $service = new PutStatusService;
        $course = Course::factory()->create([
            'status' => $currentStatus,
        ]);

        // Act
        $service(collect([$course]), $targetStatus);

        // Assert
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => $targetStatus,
        ]);
    }

    #[DataProvider('disallowedStatusProvider')]
    public function test_講座ステータス一括更新_許容しない組み合わせ_失敗(string $currentStatus, string $targetStatus): void
    {
        // Arrange
        $service = new PutStatusService;
        $course = Course::factory()->create([
            'status' => $currentStatus,
        ]);

        // Act
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($currentStatus.'は、'.$targetStatus.'に変更できません。');
        $service(collect([$course]), $targetStatus);

        // Assert
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => $currentStatus,
        ]);
    }
}
