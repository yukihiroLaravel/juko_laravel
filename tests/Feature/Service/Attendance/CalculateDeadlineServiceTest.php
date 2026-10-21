<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Attendance;

use App\Enums\Course\DeadlineTypeEnum;
use App\Services\Attendance\CalculateDeadlineService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CalculateDeadlineServiceTest extends TestCase
{
    /** AC-ENROLL-010 */
    public function test_固定期限の場合は指定された期限が返る(): void
    {
        // Arrange
        $service = new CalculateDeadlineService;
        $fixedDate = new CarbonImmutable('2025-05-10 00:00:00');
        $startAt = new CarbonImmutable('2025-05-01 12:34:56');

        // Act
        $actual = $service(
            deadlineType: DeadlineTypeEnum::FIXED_DATE->value,
            fixedDate: $fixedDate,
            relativeDays: null,
            startAt: $startAt,
        );

        // Assert
        $this->assertSame($fixedDate, $actual);
    }

    /** AC-ENROLL-010 */
    public function test_期限なしの場合はnullが返る(): void
    {
        // Arrange
        $service = new CalculateDeadlineService;
        $startAt = new CarbonImmutable('2025-05-01 12:34:56');

        // Act
        $actual = $service(
            deadlineType: DeadlineTypeEnum::NONE->value,
            fixedDate: null,
            relativeDays: null,
            startAt: $startAt,
        );

        // Assert
        $this->assertNull($actual);
    }

    /** AC-ENROLL-010 */
    public function test_相対日数の場合は開始日から指定された日数を加算した日付が返る(): void
    {
        // Arrange
        $service = new CalculateDeadlineService;
        $startAt = new CarbonImmutable('2025-05-10 10:00:00');
        $relativeDays = 5;

        // Act
        $actual = $service(
            deadlineType: DeadlineTypeEnum::RELATIVE_DAYS->value,
            fixedDate: null,
            relativeDays: $relativeDays,
            startAt: $startAt,
        );

        // Assert
        $this->assertInstanceOf(CarbonImmutable::class, $actual);
        $this->assertSame('2025-05-15 10:00:00', $actual->format('Y-m-d H:i:s'));
    }

    #[DataProvider('missingDeadlineValueProvider')]
    public function test_期限の決め方に必要な値が指定されていない場合は例外が投げられる(string $deadlineType, string $expectedMessage): void
    {
        // Arrange
        $service = new CalculateDeadlineService;
        $startAt = new CarbonImmutable('2025-05-01 12:34:56');

        // Assert
        $this->expectExceptionMessage($expectedMessage);

        // Act
        $service(
            deadlineType: $deadlineType,
            fixedDate: null,
            relativeDays: null,
            startAt: $startAt,
        );
    }

    /** @return array<string, array{string, string}> */
    public static function missingDeadlineValueProvider(): array
    {
        return [
            '固定期限で期限が未指定' => [
                DeadlineTypeEnum::FIXED_DATE->value,
                'fixed_date is required when fixed_date is selected',
            ],
            '相対日数で日数が未指定' => [
                DeadlineTypeEnum::RELATIVE_DAYS->value,
                'relative_days is required when relative_days is selected',
            ],
        ];
    }
}
