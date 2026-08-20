<?php

namespace Tests\Feature\Models;

use App\Model\Attendance;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    /**
     * ログイン率の計算例
     *
     * @return array<string, array{int, int, int}>
     */
    public static function loginRates(): array
    {
        return [
            '受講生がいない場合は0になる' => [5, 0, 0],
            'ログインした人がいない場合は0になる' => [0, 10, 0],
            '半数がログインした場合は50になる' => [5, 10, 50],
            '全員がログインした場合は100になる' => [10, 10, 100],
            '10人中7人がログインした場合は70になる' => [7, 10, 70],
        ];
    }

    /**
     * 修了率の計算例
     *
     * @return array<string, array{int, int, int}>
     */
    public static function completionRates(): array
    {
        return [
            '受講生がいない場合は0になる' => [5, 0, 0],
            '修了した人がいない場合は0になる' => [0, 10, 0],
            '半数が修了した場合は50になる' => [5, 10, 50],
            '全員が修了した場合は100になる' => [10, 10, 100],
            '7人中3人が修了した場合は端数を切り捨てて42になる' => [3, 7, 42],
        ];
    }

    #[DataProvider('loginRates')]
    public function test_ログイン率を計算する(int $loggedInCount, int $totalCount, int $expected): void
    {
        // Arrange
        // 集計に使う人数はデータプロバイダから受け取る

        // Act
        $result = Attendance::calcLoginRate($loggedInCount, $totalCount);

        // Assert
        $this->assertEquals($expected, $result);
    }

    #[DataProvider('completionRates')]
    public function test_修了率を計算する(int $completedCount, int $totalCount, int $expected): void
    {
        // Arrange
        // 集計に使う人数はデータプロバイダから受け取る

        // Act
        $result = Attendance::calcCompletionRate($completedCount, $totalCount);

        // Assert
        $this->assertEquals($expected, $result);
    }
}
