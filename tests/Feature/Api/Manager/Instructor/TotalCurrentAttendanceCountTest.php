<?php

namespace Tests\Feature\Api\Manager\Instructor;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\Student;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TotalCurrentAttendanceCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_トータル受講中受講生数取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);

        $course1 = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $course2 = Course::factory()->create(['instructor_id' => $subordinate->id]);

        // 受講中（completed_at = null）の受講レコードを3件
        Attendance::factory()->create([
            'student_id' => Student::factory(),
            'course_id' => $course1->id,
            'completed_at' => null,
        ]);
        Attendance::factory()->create([
            'student_id' => Student::factory(),
            'course_id' => $course1->id,
            'completed_at' => null,
        ]);
        Attendance::factory()->create([
            'student_id' => Student::factory(),
            'course_id' => $course2->id,
            'completed_at' => null,
        ]);

        // 完了済みの受講レコード（カウント対象外）
        Attendance::factory()->create([
            'student_id' => Student::factory(),
            'course_id' => $course1->id,
            'completed_at' => CarbonImmutable::now(),
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.total-current-attendance-count', [
            'instructor_id' => $subordinate->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'total_current_attendance_count' => 3,
            ],
        ]);
    }

    public function test_トータル受講中受講生数取得_他講師の受講はカウントされない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $subordinate = Instructor::factory()->create(['type' => 'instructor']);
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        ManageInstructor::factory()->create([
            'manager_id' => $manager->id,
            'instructor_id' => $subordinate->id,
        ]);

        $subordinateCourse = Course::factory()->create(['instructor_id' => $subordinate->id]);
        $otherCourse = Course::factory()->create(['instructor_id' => $otherInstructor->id]);

        Attendance::factory()->create([
            'student_id' => Student::factory(),
            'course_id' => $subordinateCourse->id,
            'completed_at' => null,
        ]);
        // 別講師の講座への受講はカウント対象外
        Attendance::factory()->create([
            'student_id' => Student::factory(),
            'course_id' => $otherCourse->id,
            'completed_at' => null,
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.total-current-attendance-count', [
            'instructor_id' => $subordinate->id,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.total_current_attendance_count', 1);
    }

    public function test_権限がない_トータル受講中受講生数取得_失敗(): void
    {
        // Arrange — 配下ではない講師の情報を取得しようとする
        $manager = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create(['type' => 'instructor']);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.total-current-attendance-count', [
            'instructor_id' => $otherInstructor->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'This action is unauthorized.',
        ]);
    }

    public function test_マネージャーではない_失敗(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.instructors.total-current-attendance-count', [
            'instructor_id' => $otherInstructor->id,
        ]));

        // Assert
        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Forbidden, not allowed to use manager api.',
        ]);
    }

    public function test_存在しない講師_id_失敗(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act — 存在しないinstructor_idを指定
        $response = $this->getJson(route('manager.instructors.total-current-attendance-count', [
            'instructor_id' => 99999,
        ]));

        // Assert
        $response->assertStatus(422);
    }
}
