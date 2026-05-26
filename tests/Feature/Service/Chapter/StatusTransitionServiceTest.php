<?php

namespace Tests\Feature\Service\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use App\Services\Chapter\StatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StatusTransitionServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: StatusEnum, 1: StatusEnum}>
     */
    public static function allowedStatusProvider(): array
    {
        return [
            [StatusEnum::DRAFT, StatusEnum::PUBLIC],
            [StatusEnum::PUBLIC, StatusEnum::PRIVATE],
            [StatusEnum::PRIVATE, StatusEnum::PUBLIC],
            [StatusEnum::PUBLIC, StatusEnum::PUBLIC],
            [StatusEnum::PRIVATE, StatusEnum::PRIVATE],
        ];
    }

    /**
     * @return array<int, array{0: StatusEnum, 1: StatusEnum}>
     */
    public static function disallowedStatusProvider(): array
    {
        return [
            [StatusEnum::DRAFT, StatusEnum::PRIVATE],
        ];
    }

    #[DataProvider('allowedStatusProvider')]
    public function test_変更するチャプターのステータス検証_許容する組み合わせ_成功(StatusEnum $currentStatus, StatusEnum $targetStatus): void
    {
        // Arrange
        $service = new StatusTransitionService;
        $chapters = Chapter::factory()->create([
            'status' => $currentStatus->value,
        ]);

        // Act
        $service(collect([$chapters]), $targetStatus);

        // Assert
        $this->assertTrue(true);
    }

    #[DataProvider('disallowedStatusProvider')]
    public function test_変更するチャプターのステータス検証_許容しない組み合わせ_失敗(StatusEnum $currentStatus, StatusEnum $targetStatus): void
    {
        // Arrange
        $service = new StatusTransitionService;
        $chapters = Chapter::factory()->create([
            'status' => $currentStatus->value,
        ]);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($currentStatus->value.'は、'.$targetStatus->value.'に変更できません。');

        // Act
        $service(collect([$chapters]), $targetStatus);

        // Assert
        $this->assertFalse(true);
    }
}
