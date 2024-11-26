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
}
