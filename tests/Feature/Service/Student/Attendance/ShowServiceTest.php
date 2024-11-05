<?php

namespace Tests\Feature\Service\Student\Attendance;

use Tests\TestCase;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Student;
use App\Dto\Student\Attendance\ShowDto;
use App\Services\Student\Attendance\ShowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShowServiceTest extends TestCase
{
    use RefreshDatabase;

    // setup
    public function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_正常系_受講中の講座の詳細情報を取得する_認可OK()
    {
        // arrange
        /** @var Student $student */
        $student = Student::find(1);

        $attendanceId = 1;
        $userId = $student->id;
        $showDto = new ShowDto($attendanceId, $userId);
        $service = new ShowService();
        // act
        $attendance = $service($showDto);
        // assert
        // 戻り値がAttendanceであること
        $this->assertInstanceOf(Attendance::class, $attendance);

        // $attendance->idが$attendanceIdであること
        $this->assertEquals($attendanceId, $attendance->id);

        // $attendance->progressがintの値であること
        $this->assertIsInt($attendance->progress);

        // $attendance->courseに値があり、Course::classであること
        $this->assertNotNull($attendance->course);
        $this->assertInstanceOf(Course::class, $attendance->course);
    }

    public function test_異常系_受講中の講座の詳細情報を取得する_認可NG()
    {
        // arrange
        /** @var Student $student */
        $student = Student::find(1);

        $attendanceId = 2;
        $userId = $student->id;
        $showDto = new ShowDto($attendanceId, $userId);
        $service = new ShowService();

        // AuthorizationExceptionの例外が発生すること
        $this->expectException(AuthorizationException::class);

        // act
        $service($showDto);
    }
}
