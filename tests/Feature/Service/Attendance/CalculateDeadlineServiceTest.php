<?php

namespace Tests\Feature\Service\Attendance;

use App\Enums\Course\DeadlineTypeEnum;
use App\Services\Attendance\CalculateDeadlineService;
use DateTimeImmutable;
use DomainException;
use Tests\TestCase;

class CalculateDeadlineServiceTest extends TestCase
{
    private CalculateDeadlineService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CalculateDeadlineService;
    }

    public function test_正常系_期限なし_nullを返す()
    {
        // arrange
        $deadlineType = DeadlineTypeEnum::NONE->value;
        $fixedDate = null;
        $relativeDays = null;
        $startAt = new DateTimeImmutable('2024-01-01');

        // act
        $result = ($this->service)(
            $deadlineType,
            $fixedDate,
            $relativeDays,
            $startAt
        );

        // assert
        $this->assertNull($result);
    }

    public function test_正常系_固定日付_指定日付を返す()
    {
        // arrange
        $deadlineType = DeadlineTypeEnum::FIXED_DATE->value;
        $fixedDate = new DateTimeImmutable('2024-03-15');
        $relativeDays = null;
        $startAt = new DateTimeImmutable('2024-01-01');

        // act
        $result = ($this->service)(
            $deadlineType,
            $fixedDate,
            $relativeDays,
            $startAt
        );

        // assert
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertEquals('2024-03-15', $result->format('Y-m-d'));
    }

    public function test_正常系_相対日数_開始日から指定日数後を返す()
    {
        // arrange
        $deadlineType = DeadlineTypeEnum::RELATIVE_DAYS->value;
        $fixedDate = null;
        $relativeDays = 30;
        $startAt = new DateTimeImmutable('2024-01-01');

        // act
        $result = ($this->service)(
            $deadlineType,
            $fixedDate,
            $relativeDays,
            $startAt
        );

        // assert
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertEquals('2024-01-31', $result->format('Y-m-d'));
    }

    public function test_正常系_相対日数でrelative_daysがnull_nullを返す()
    {
        // arrange
        $deadlineType = DeadlineTypeEnum::RELATIVE_DAYS->value;
        $fixedDate = null;
        $relativeDays = null;
        $startAt = new DateTimeImmutable('2024-01-01');

        // act
        $result = ($this->service)(
            $deadlineType,
            $fixedDate,
            $relativeDays,
            $startAt
        );

        // assert
        $this->assertNull($result);
    }

    public function test_異常系_未対応の期限タイプ_domain_exceptionを投げる()
    {
        // arrange
        $deadlineType = 'unsupported_type';
        $fixedDate = null;
        $relativeDays = null;
        $startAt = new DateTimeImmutable('2024-01-01');

        // assert
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Unsupported deadline_type: unsupported_type');

        // act
        ($this->service)(
            $deadlineType,
            $fixedDate,
            $relativeDays,
            $startAt
        );
    }

    public function test_境界値_相対日数0日_開始日と同じ日を返す()
    {
        // arrange
        $deadlineType = DeadlineTypeEnum::RELATIVE_DAYS->value;
        $fixedDate = null;
        $relativeDays = 0;
        $startAt = new DateTimeImmutable('2024-01-01');

        // act
        $result = ($this->service)(
            $deadlineType,
            $fixedDate,
            $relativeDays,
            $startAt
        );

        // assert
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertEquals('2024-01-01', $result->format('Y-m-d'));
    }

    public function test_境界値_相対日数マイナス_過去日を返す()
    {
        // arrange
        $deadlineType = DeadlineTypeEnum::RELATIVE_DAYS->value;
        $fixedDate = null;
        $relativeDays = -5;
        $startAt = new DateTimeImmutable('2024-01-10');

        // act
        $result = ($this->service)(
            $deadlineType,
            $fixedDate,
            $relativeDays,
            $startAt
        );

        // assert
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertEquals('2024-01-05', $result->format('Y-m-d'));
    }

    public function test_正常系_大きな相対日数_正しく計算される()
    {
        // arrange
        $deadlineType = DeadlineTypeEnum::RELATIVE_DAYS->value;
        $fixedDate = null;
        $relativeDays = 365;
        $startAt = new DateTimeImmutable('2024-01-01');

        // act
        $result = ($this->service)(
            $deadlineType,
            $fixedDate,
            $relativeDays,
            $startAt
        );

        // assert
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertEquals('2024-12-31', $result->format('Y-m-d'));
    }

    public function test_正常系_月末日からの相対日数計算()
    {
        // arrange
        $deadlineType = DeadlineTypeEnum::RELATIVE_DAYS->value;
        $fixedDate = null;
        $relativeDays = 1;
        $startAt = new DateTimeImmutable('2024-01-31');

        // act
        $result = ($this->service)(
            $deadlineType,
            $fixedDate,
            $relativeDays,
            $startAt
        );

        // assert
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertEquals('2024-02-01', $result->format('Y-m-d'));
    }

    public function test_正常系_うるう年の2月29日からの計算()
    {
        // arrange
        $deadlineType = DeadlineTypeEnum::RELATIVE_DAYS->value;
        $fixedDate = null;
        $relativeDays = 1;
        $startAt = new DateTimeImmutable('2024-02-29');

        // act
        $result = ($this->service)(
            $deadlineType,
            $fixedDate,
            $relativeDays,
            $startAt
        );

        // assert
        $this->assertInstanceOf(DateTimeImmutable::class, $result);
        $this->assertEquals('2024-03-01', $result->format('Y-m-d'));
    }
}
