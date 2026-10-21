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

    /** AC-AUTHOR-022 */
    #[DataProvider('allowedTransitions')]
    public function test_許される公開状態の変更は受け付けられる(StatusEnum $current, StatusEnum $target): void
    {
        // Arrange

        // Assert
        $this->expectNotToPerformAssertions();

        // Act
        ($this->service)($current, $target);
    }

    /** AC-AUTHOR-022 */
    #[DataProvider('disallowedTransitions')]
    public function test_許されない公開状態の変更は拒否される(StatusEnum $current, StatusEnum $target): void
    {
        // Arrange

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage(
            'ステータスを「'.$current->value.'」から「'.$target->value.'」へ変更することはできません。'
        );

        // Act
        ($this->service)($current, $target);
    }
}
