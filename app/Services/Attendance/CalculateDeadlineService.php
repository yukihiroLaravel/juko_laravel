<?php
declare(strict_types=1);

namespace App\Services\Attendance;

use App\Enums\Course\DeadlineTypeEnum;
use DateTimeImmutable;
use DomainException;

/**
 * 受講期限を算出する純粋ロジック
 */
final class CalculateDeadlineService
{
    public function __invoke(
        string $deadlineType,
        ?DateTimeImmutable $fixedDate,
        ?int $relativeDays,
        DateTimeImmutable $startAt
    ): ?DateTimeImmutable {
        if ($deadlineType === DeadlineTypeEnum::FIXED_DATE->value && $fixedDate === null) {
            throw new DomainException('fixed_date is required when deadline_type=FIXED_DATE');
        }
        if ($deadlineType === DeadlineTypeEnum::RELATIVE_DAYS->value && $relativeDays === null) {
            throw new DomainException('relative_days is required when deadline_type=RELATIVE_DAYS');
        }

        return match ($deadlineType) {
            DeadlineTypeEnum::NONE->value => null,
            DeadlineTypeEnum::FIXED_DATE->value => $fixedDate,
            DeadlineTypeEnum::RELATIVE_DAYS->value => $startAt->modify(sprintf('+%d days', $relativeDays)),
            default => throw new DomainException("Unsupported deadline_type: {$deadlineType}"),
        };
    }
}
