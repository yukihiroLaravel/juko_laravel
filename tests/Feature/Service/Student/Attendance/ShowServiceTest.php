<?php

namespace Tests\Feature\Service\Student\Attendance;

use App\Dto\Student\Attendance\ShowDto;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Student;
use App\Services\Student\Attendance\ShowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_正常系_受講中の講座の詳細情報を取得する_認可_ok()
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        $showDto = new ShowDto($attendance->id, $student->id);
        $service = new ShowService;

        // Act
        $result = $service($showDto);

        // Assert
        $this->assertInstanceOf(Attendance::class, $result);
        $this->assertEquals($attendance->id, $result->id);
        $this->assertNotNull($result->course);
        $this->assertInstanceOf(Course::class, $result->course);
    }

    public function test_異常系_受講中の講座の詳細情報を取得する_認可_ng()
    {
        // Arrange — 別の生徒の受講にアクセスしようとするケース
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $ownerStudent = Student::factory()->create();
        $attendance = Attendance::factory()->create([
            'student_id' => $ownerStudent->id,
            'course_id' => $course->id,
        ]);

        $otherStudent = Student::factory()->create();
        $showDto = new ShowDto($attendance->id, $otherStudent->id);
        $service = new ShowService;

        // Assert
        $this->expectException(AuthorizationException::class);

        // Act
        $service($showDto);
    }
}
