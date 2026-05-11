<?php

namespace Tests\Feature\Models;

use App\Model\Attendance;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    public function test_ログイン率を計算する(): void
    {
        $result = Attendance::calcLoginRate(5, 0);
        $this->assertEquals(0, $result);

        $result = Attendance::calcLoginRate(0, 10);
        $this->assertEquals(0, $result);

        $result = Attendance::calcLoginRate(5, 10);
        $this->assertEquals(50, $result);

        $result = Attendance::calcLoginRate(10, 10);
        $this->assertEquals(100, $result);

        $result = Attendance::calcLoginRate(7, 10);
        $this->assertEquals(70, $result);
    }

    public function test_修了率を計算する(): void
    {
        // 受講生が0人の場合は0を返す
        $result = Attendance::calcCompletionRate(5, 0);
        $this->assertEquals(0, $result);

        // 修了者が0人の場合は0を返す
        $result = Attendance::calcCompletionRate(0, 10);
        $this->assertEquals(0, $result);

        // 半数が修了している場合は50を返す
        $result = Attendance::calcCompletionRate(5, 10);
        $this->assertEquals(50, $result);

        // 全員修了している場合は100を返す
        $result = Attendance::calcCompletionRate(10, 10);
        $this->assertEquals(100, $result);

        // 端数は切り捨てる（3/7 ≒ 42.85 → 42）
        $result = Attendance::calcCompletionRate(3, 7);
        $this->assertEquals(42, $result);
    }
}
