<?php

namespace Tests\Feature\Service\Course;

use App\Enums\Course\StatusEnum;
use App\Model\Course;
use App\Services\Course\PutStatusService;
use App\Services\Course\StatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PutStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function allowedStatusProvider(): array
    {
        return [
            '下書きから公開' => [StatusEnum::DRAFT->value, StatusEnum::PUBLIC->value],
            '公開から非公開' => [StatusEnum::PUBLIC->value, StatusEnum::PRIVATE->value],
            '非公開から公開' => [StatusEnum::PRIVATE->value, StatusEnum::PUBLIC->value],
            '公開から公開（変更なし）' => [StatusEnum::PUBLIC->value, StatusEnum::PUBLIC->value],
            '非公開から非公開（変更なし）' => [StatusEnum::PRIVATE->value, StatusEnum::PRIVATE->value],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function disallowedStatusProvider(): array
    {
        return [
            '下書きから非公開' => [StatusEnum::DRAFT->value, StatusEnum::PRIVATE->value],
        ];
    }

    /** AC-AUTHOR-009 */
    #[DataProvider('allowedStatusProvider')]
    public function test_許される変更なら講座の公開状態を変更できる(string $currentStatus, string $targetStatus): void
    {
        // Arrange
        $service = new PutStatusService(new StatusTransitionService);
        $course = Course::factory()->create([
            'status' => $currentStatus,
        ]);

        // Act
        $service(collect([$course]), $targetStatus);

        // Assert
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => $targetStatus,
        ]);
    }

    /** AC-AUTHOR-009 */
    #[DataProvider('disallowedStatusProvider')]
    public function test_下書きの講座を非公開にしようとすると変更できず元の公開状態のまま(string $currentStatus, string $targetStatus): void
    {
        // Arrange
        $service = new PutStatusService(new StatusTransitionService);
        $course = Course::factory()->create([
            'status' => $currentStatus,
        ]);

        // Act & Assert
        try {
            $service(collect([$course]), $targetStatus);
            $this->fail('ValidationExceptionが発生しませんでした。');
        } catch (ValidationException $e) {
            $this->assertSame(
                'ステータスを「'.$currentStatus.'」から「'.$targetStatus.'」へ変更することはできません。',
                $e->errors()['status'][0]
            );
        }

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'status' => $currentStatus,
        ]);
    }

    /** AC-AUTHOR-012, AC-COMMON-014 */
    public function test_一部に許されない変更が含まれると全講座が元の公開状態のまま(): void
    {
        // Arrange
        $service = new PutStatusService(new StatusTransitionService);
        $publicCourse = Course::factory()->create(['status' => StatusEnum::PUBLIC->value]);
        $draftCourse = Course::factory()->create(['status' => StatusEnum::DRAFT->value]);

        // Act & Assert
        try {
            $service(collect([$publicCourse, $draftCourse]), StatusEnum::PRIVATE->value);
            $this->fail('ValidationExceptionが発生しませんでした。');
        } catch (ValidationException) {
        }

        $this->assertDatabaseHas('courses', ['id' => $publicCourse->id, 'status' => StatusEnum::PUBLIC->value]);
        $this->assertDatabaseHas('courses', ['id' => $draftCourse->id, 'status' => StatusEnum::DRAFT->value]);
    }
}
