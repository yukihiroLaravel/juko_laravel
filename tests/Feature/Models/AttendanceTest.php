<?php

namespace Tests\Feature\Models;

use App\Model\Attendance;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    /**
     * ログイン率と修了率で共通の計算例（該当人数、受講生の総数、期待する割合）
     *
     * @return array<string, array{int, int, int}>
     */
    public static function rates(): array
    {
        return [
            '受講生がいない場合は0になる' => [5, 0, 0],
            '該当する人がいない場合は0になる' => [0, 10, 0],
            '半数が該当する場合は50になる' => [5, 10, 50],
            '全員が該当する場合は100になる' => [10, 10, 100],
            '10人中7人が該当する場合は70になる' => [7, 10, 70],
            '7人中3人が該当する場合は端数を切り捨てて42になる' => [3, 7, 42],
        ];
    }

    /** AC-ANALYTICS-001 */
    #[DataProvider('rates')]
    public function test_ログイン率を計算する(int $loggedInCount, int $totalCount, int $expected): void
    {
        // Arrange
        // 人数は DataProvider から受け取る

        // Act
        $result = Attendance::calcLoginRate($loggedInCount, $totalCount);

        // Assert
        $this->assertEquals($expected, $result);
    }

    /** AC-ANALYTICS-001 */
    #[DataProvider('rates')]
    public function test_修了率を計算する(int $completedCount, int $totalCount, int $expected): void
    {
        // Arrange
        // 人数は DataProvider から受け取る

        // Act
        $result = Attendance::calcCompletionRate($completedCount, $totalCount);

        // Assert
        $this->assertEquals($expected, $result);
    }
}
