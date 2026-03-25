<?php

namespace Tests\Feature\Api\Instructor\Notification;

use App\Enums\Notification\StatusEnum;
use App\Enums\Notification\TypeEnum;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\CourseDeadline;
use App\Model\Instructor;
use App\Model\Notification;
use App\Model\Student;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_お知らせ一覧取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $instructor->id,
            'capacity' => 10,
        ]);
        $tag = Tag::factory()->create(['instructor_id' => $instructor->id]);
        $course->tags()->attach($tag->id);
        CourseDeadline::factory()->create([
            'course_id' => $course->id,
            'fixed_date' => '2026-12-31',
        ]);
        $notification = Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
            'title' => 'テストお知らせ',
            'content' => 'テスト内容',
            'status' => StatusEnum::PUBLIC,
            'type' => TypeEnum::ONCE,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-30',
        ]);
        // 受講生を2名登録
        Attendance::factory()->count(2)->create(['course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notification.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'data' => [
                'notifications' => [
                    [
                        'notification_id' => $notification->id,
                        'course_id' => $course->id,
                        'course_title' => $course->title,
                        'title' => 'テストお知らせ',
                        'content' => 'テスト内容',
                        'status' => StatusEnum::PUBLIC->value,
                        'type' => TypeEnum::ONCE->value,
                        'start_date' => '2026-04-01',
                        'end_date' => '2026-04-30',
                        'course' => [
                            'course_id' => $course->id,
                            'title' => $course->title,
                            'image' => $course->image,
                            'status' => Course::STATUS_PUBLIC,
                            'capacity' => 10,
                            'deadline_type' => 'none',
                            'current_attendance_count' => 2,
                            'course_deadline' => [
                                'course_deadline_id' => $course->courseDeadline->id,
                                'fixed_date' => '2026-12-31',
                                'relative_days' => null,
                            ],
                        ],
                        'tags' => [
                            [
                                'tag_id' => $tag->id,
                                'content' => $tag->content,
                            ],
                        ],
                    ],
                ],
                'pagination' => [
                    'page' => 1,
                    'total' => 1,
                ],
            ],
        ]);
    }

    public function test_お知らせ一覧取得_受講生がいない場合_current_attendance_countが0(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notification.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.notifications.0.course.current_attendance_count', 0);
    }

    public function test_お知らせ一覧取得_複数件_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Notification::factory()->count(3)->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notification.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data.notifications');
        $response->assertJsonPath('data.pagination.total', 3);
    }

    public function test_お知らせ一覧取得_0件_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notification.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data.notifications');
        $response->assertJsonPath('data.pagination.total', 0);
    }

    public function test_お知らせ一覧取得_他の講師のお知らせは取得されない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $otherInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $otherCourse = Course::factory()->create(['instructor_id' => $otherInstructor->id]);
        Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        Notification::factory()->create([
            'instructor_id' => $otherInstructor->id,
            'course_id' => $otherCourse->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notification.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data.notifications');
    }

    public function test_お知らせ一覧取得_ページネーション_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Notification::factory()->count(5)->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notification.index', [
            'per_page' => 2,
            'page' => 1,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.notifications');
        $response->assertJsonPath('data.pagination.total', 5);
        $response->assertJsonPath('data.pagination.page', 1);
    }

    public function test_お知らせ一覧取得_ページネーション_2ページ目_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Notification::factory()->count(5)->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notification.index', [
            'per_page' => 2,
            'page' => 2,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.notifications');
        $response->assertJsonPath('data.pagination.page', 2);
    }

    public function test_お知らせ一覧取得_論理削除済み受講はカウントされない(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        Notification::factory()->create([
            'instructor_id' => $instructor->id,
            'course_id' => $course->id,
        ]);
        // 通常の受講2件
        Attendance::factory()->count(2)->create(['course_id' => $course->id]);
        // 論理削除済みの受講1件
        $deletedAttendance = Attendance::factory()->create(['course_id' => $course->id]);
        $deletedAttendance->delete();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.notification.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.notifications.0.course.current_attendance_count', 2);
    }

    public function test_お知らせ一覧取得_未認証_失敗(): void
    {
        // Act
        $response = $this->getJson(route('instructor.notification.index'));

        // Assert
        $response->assertStatus(401);
    }

    public function test_お知らせ一覧取得_生徒でのアクセス_失敗(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student, 'sanctum');

        // Act
        $response = $this->getJson(route('instructor.notification.index'));

        // Assert
        $response->assertStatus(401);
    }
}
