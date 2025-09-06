<?php

declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\Course\DeadlineTypeEnum;
use DateTimeImmutable;
use DomainException;

/**
 * 受講期限（attendance_deadline）を算出する純粋ロジック
 */
final class AttendanceDeadlineCalculator
{
    public function __invoke(
        string $deadlineType,
        ?DateTimeImmutable $fixedDate,
        ?int $relativeDays,
        DateTimeImmutable $startAt
    ): ?DateTimeImmutable {
        return match ($deadlineType) {
            DeadlineTypeEnum::NONE->value => null,
            DeadlineTypeEnum::FIXED_DATE->value => $fixedDate,
            DeadlineTypeEnum::RELATIVE_DAYS->value => $relativeDays !== null
                ? $startAt->modify(sprintf('+%d days', $relativeDays))
                : null,
            default => throw new DomainException("Unsupported deadline_type: {$deadlineType}"),
        };
    }
}
