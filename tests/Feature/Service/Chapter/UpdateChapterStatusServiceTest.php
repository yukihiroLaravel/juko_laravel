<?php

namespace Tests\Feature\Service\Chapter;

use App\Enums\Chapter\StatusEnum;
use App\Model\Chapter;
use App\Services\Chapter\StatusTransitionService;
use App\Services\Chapter\UpdateChapterStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UpdateChapterStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    private UpdateChapterStatusService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new UpdateChapterStatusService(new StatusTransitionService);
    }

    public function test_指定したチャプターのステータスを更新できる(): void
    {
        // Arrange
        $chapter = Chapter::factory()->create(['status' => StatusEnum::PUBLIC->value]);

        // Act
        ($this->service)(collect([$chapter->id]), StatusEnum::PRIVATE->value);

        // Assert
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'status' => StatusEnum::PRIVATE->value,
        ]);
    }

    public function test_複数のチャプターをまとめて更新できる(): void
    {
        // Arrange（公開・非公開いずれからも公開への遷移は許容される）
        $publicChapter = Chapter::factory()->create(['status' => StatusEnum::PUBLIC->value]);
        $privateChapter = Chapter::factory()->create(['status' => StatusEnum::PRIVATE->value]);

        // Act
        ($this->service)(collect([$publicChapter->id, $privateChapter->id]), StatusEnum::PUBLIC->value);

        // Assert
        $this->assertDatabaseHas('chapters', ['id' => $publicChapter->id, 'status' => StatusEnum::PUBLIC->value]);
        $this->assertDatabaseHas('chapters', ['id' => $privateChapter->id, 'status' => StatusEnum::PUBLIC->value]);
    }

    public function test_許容されない遷移を含む場合は例外を発生させステータスを更新しない(): void
    {
        // Arrange（下書きから非公開への遷移は許容されない）
        $chapter = Chapter::factory()->create(['status' => StatusEnum::DRAFT->value]);

        // Act & Assert
        try {
            ($this->service)(collect([$chapter->id]), StatusEnum::PRIVATE->value);
            $this->fail('ValidationExceptionが発生しませんでした。');
        } catch (ValidationException $e) {
            $this->assertSame(
                'ステータスを「'.StatusEnum::DRAFT->value.'」から「'.StatusEnum::PRIVATE->value.'」へ変更することはできません。',
                $e->errors()['status'][0]
            );
        }

        // ステータスは更新されていない
        $this->assertDatabaseHas('chapters', [
            'id' => $chapter->id,
            'status' => StatusEnum::DRAFT->value,
        ]);
    }

    public function test_一部に許容されない遷移が含まれる場合は全件更新しない(): void
    {
        // Arrange（公開→非公開は可だが、下書き→非公開は不可のため全体が失敗する）
        $publicChapter = Chapter::factory()->create(['status' => StatusEnum::PUBLIC->value]);
        $draftChapter = Chapter::factory()->create(['status' => StatusEnum::DRAFT->value]);

        // Act & Assert
        try {
            ($this->service)(collect([$publicChapter->id, $draftChapter->id]), StatusEnum::PRIVATE->value);
            $this->fail('ValidationExceptionが発生しませんでした。');
        } catch (ValidationException) {
            // 検証段階で中断するため、更新は一切行われない
        }

        // Assert（原子性: いずれのチャプターも元のステータスのまま）
        $this->assertDatabaseHas('chapters', ['id' => $publicChapter->id, 'status' => StatusEnum::PUBLIC->value]);
        $this->assertDatabaseHas('chapters', ['id' => $draftChapter->id, 'status' => StatusEnum::DRAFT->value]);
    }
}
