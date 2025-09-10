<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Attendance;

use App\Enums\Course\DeadlineTypeEnum;
use App\Services\Attendance\CalculateDeadlineService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CalculateDeadlineServiceTest extends TestCase
{
    public function test_固定期限の場合は指定された期限が返る(): void
    {
        // Arrange
        $service = new CalculateDeadlineService;
        $fixedDate = new DateTimeImmutable('2025-05-10 00:00:00');
        $startAt = new DateTimeImmutable('2025-05-01 12:34:56');

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

    public function test_期限なしの場合はnullが返る(): void
    {
        // Arrange
        $service = new CalculateDeadlineService;
        $startAt = new DateTimeImmutable('2025-05-01 12:34:56');

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

    public function test_相対日数の場合は開始日から指定された日数を加算した日付が返る(): void
    {
        // Arrange
        $service = new CalculateDeadlineService;
        $startAt = new DateTimeImmutable('2025-05-10 10:00:00');
        $relativeDays = 5;

        // Act
        $actual = $service(
            deadlineType: DeadlineTypeEnum::RELATIVE_DAYS->value,
            fixedDate: null,
            relativeDays: $relativeDays,
            startAt: $startAt,
        );

        // Assert
        $this->assertInstanceOf(DateTimeImmutable::class, $actual);
        $this->assertSame('2025-05-15 10:00:00', $actual->format('Y-m-d H:i:s'));
    }

    public function test_固定期限で期限が指定されていない場合は例外が投げられる(): void
    {
        // Arrange
        $service = new CalculateDeadlineService;
        $startAt = new DateTimeImmutable('2025-05-01 12:34:56');

        // Assert
        $this->expectExceptionMessage('fixed_date is required when fixed_date is selected');

        // Act
        $service(
            deadlineType: DeadlineTypeEnum::FIXED_DATE->value,
            fixedDate: null,
            relativeDays: null,
            startAt: $startAt,
        );
    }

    public function test_相対日数で日数が指定されていない場合は例外が投げられる(): void
    {
        // Arrange
        $service = new CalculateDeadlineService;
        $startAt = new DateTimeImmutable('2025-05-01 12:34:56');

        // Assert
        $this->expectExceptionMessage('relative_days is required when relative_days is selected');

        // Act
        $service(
            deadlineType: DeadlineTypeEnum::RELATIVE_DAYS->value,
            fixedDate: null,
            relativeDays: null,
            startAt: $startAt,
        );
    }
}
