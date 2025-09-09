<?php

declare(strict_types=1);

namespace Tests\Feature\Service\Attendance;

use App\Enums\Course\DeadlineTypeEnum;
use App\Services\Attendance\CalculateDeadlineService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * CalculateDeadlineService の正常系テスト
 * - FIXED_DATE: 指定日がそのまま返る
 * - RELATIVE_DAYS: startAt + relativeDays が返る
 */
final class CalculateDeadlineServiceTest extends TestCase
{
    public function test_fixed_date_returns_same_value(): void
    {
        // Arrange
        $service   = new CalculateDeadlineService();
        $fixedDate = new DateTimeImmutable('2025-05-10 00:00:00');
        $startAt   = new DateTimeImmutable('2025-05-01 12:34:56');

        // Act
        $actual = $service(
            deadlineType: DeadlineTypeEnum::FIXED_DATE->value,
            fixedDate:    $fixedDate,
            relativeDays: null,
            startAt:      $startAt,
        );

        // Assert
        $this->assertSame($fixedDate, $actual);
    }

    public function test_relative_days_returns_expected_deadline(): void
    {
        // Arrange
        $service = new CalculateDeadlineService();
        $startAt = new DateTimeImmutable('2025-05-10 10:00:00');
        $relativeDays = 5;
        $expected = $startAt->modify(sprintf('+%d days', $relativeDays));

        // Act
        $actual = $service(
            deadlineType: DeadlineTypeEnum::RELATIVE_DAYS->value,
            fixedDate:    null,
            relativeDays: $relativeDays,
            startAt:      $startAt,
        );

        // Assert
        $this->assertInstanceOf(DateTimeImmutable::class, $actual);
        $this->assertSame($expected->format('c'), $actual->format('c'));
    }
}