<?php

namespace Tests\Feature\Service\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Services\Chapter\StatusTransitionService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StatusTransitionServiceTest extends TestCase
{
    private StatusTransitionService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new StatusTransitionService;
    }

    /**
     * 許容される遷移の組み合わせ
     *
     * @return array<string, array{StatusEnum, StatusEnum}>
     */
    public static function allowedTransitions(): array
    {
        return [
            '下書きから公開' => [StatusEnum::DRAFT, StatusEnum::PUBLIC],
            '公開から非公開' => [StatusEnum::PUBLIC, StatusEnum::PRIVATE],
            '非公開から公開' => [StatusEnum::PRIVATE, StatusEnum::PUBLIC],
            '公開から公開（変更なし）' => [StatusEnum::PUBLIC, StatusEnum::PUBLIC],
            '非公開から非公開（変更なし）' => [StatusEnum::PRIVATE, StatusEnum::PRIVATE],
            '下書きから下書き（変更なし）' => [StatusEnum::DRAFT, StatusEnum::DRAFT],
        ];
    }

    /**
     * 許容されない遷移の組み合わせ
     *
     * @return array<string, array{StatusEnum, StatusEnum}>
     */
    public static function disallowedTransitions(): array
    {
        return [
            '下書きから非公開' => [StatusEnum::DRAFT, StatusEnum::PRIVATE],
            '公開から下書き' => [StatusEnum::PUBLIC, StatusEnum::DRAFT],
            '非公開から下書き' => [StatusEnum::PRIVATE, StatusEnum::DRAFT],
        ];
    }

    #[DataProvider('allowedTransitions')]
    public function test_許容される遷移は例外を発生させない(StatusEnum $current, StatusEnum $target): void
    {
        $this->expectNotToPerformAssertions();

        ($this->service)($current, $target);
    }

    #[DataProvider('disallowedTransitions')]
    public function test_許容されない遷移は例外を発生させる(StatusEnum $current, StatusEnum $target): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            'ステータスを「'.$current->value.'」から「'.$target->value.'」へ変更することはできません。'
        );

        ($this->service)($current, $target);
    }
}
