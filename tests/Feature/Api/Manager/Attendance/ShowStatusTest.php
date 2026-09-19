<?php

namespace Tests\Feature\Api\Manager\Attendance;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowStatusTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-17 12:00:00'));
    }

    #[\Override]
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_当日の出席状況を取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_今月の出席状況を取得_成功(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_無効のパラメータ(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => 9999,
            'period' => 'invalid',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'period',
        ]);
    }

    public function test_配下ではない講師の講座_失敗(): void
    {
        // Arrange — 別のマネージャーの講座
        $otherManager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $otherManager->id]);
        $manager = Instructor::factory()->create();
        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_マネージャーではない講師_失敗(): void
    {
        // Arrange — マネージャーではない講師
        $nonManager = Instructor::factory()->create(['type' => 'instructor']);
        $course = Course::factory()->create(['instructor_id' => $nonManager->id]);
        $this->actingAs($nonManager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_公開レッスンのみ完了していればチャプター完了としてカウントされる(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();

        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);

        // 公開レッスン
        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
        ]);

        // 下書きレッスン
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);

        // 非公開レッスン
        Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        // 公開レッスンだけ完了
        LessonAttendance::factory()
            ->completed()
            ->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $publicLesson->id,
            ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 1,
        ]);
    }

    public function test_下書きチャプターは完了チャプター数に含まれない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();

        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::DRAFT->value,
        ]);

        $lesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()
            ->completed()
            ->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
            ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 0,
        ]);
    }

    public function test_公開レッスンが0件のチャプターは完了チャプター数に含まれない(): void
    {
        // Arrange
        $manager = Instructor::factory()->create();

        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);

        // 下書きレッスンのみ作成
        $draftLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::DRAFT->value,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        // 下書きレッスンを完了済みにする
        LessonAttendance::factory()
            ->completed()
            ->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $draftLesson->id,
            ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 0,
        ]);
    }

    public function test_学び直しで段階が受講中に戻ったレッスンも完了日時が当日なら当日の完了したレッスン件数に含める(): void
    {
        // Arrange — 当日に完了したあと、学び直しで段階を受講中に戻した
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => CarbonImmutable::parse('2026-09-17 09:00:00'),
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 1,
        ]);
    }

    public function test_段階が完了でも完了日時がなければ当日の完了したレッスン件数に含めない(): void
    {
        // Arrange — 段階は完了だが完了日時が記録されていない
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $manager->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => null,
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 0,
        ]);
    }

    public function test_非公開レッスンの更新日時が当日でも公開レッスンを当日に完了していないチャプターは当日の完了件数に含めない(): void
    {
        // Arrange — 公開レッスンは40日前に完了し、非公開レッスンの受講状況のみ当日に更新
        $manager = Instructor::factory()->create();

        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);

        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);

        $publicLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $privateLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        // 公開レッスンは40日前に完了
        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $publicLesson->id,
            'completed_at' => CarbonImmutable::now()->subDays(40),
            'updated_at' => CarbonImmutable::now()->subDays(40),
        ]);

        // 非公開レッスンも過去に完了しているが、受講状況だけ今日更新
        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $privateLesson->id,
            'completed_at' => CarbonImmutable::now()->subDays(40),
            'updated_at' => CarbonImmutable::now(),
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 0,
        ]);
    }

    public function test_過去に完了して当日に更新されたレッスンは当日の完了したレッスン件数に含めない(): void
    {
        // Arrange — 40日前に完了したレッスンの受講状況を当日に更新
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);
        $lesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'completed_at' => CarbonImmutable::now()->subDays(40),
            'updated_at' => CarbonImmutable::now(),
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 0,
        ]);
    }

    public function test_完了日時が当日なら更新日時に関係なく当日の完了したレッスン件数に含める(): void
    {
        // Arrange — 完了日時は当日、更新日時は過去
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);
        $lesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'completed_at' => CarbonImmutable::parse('2026-09-17 09:00:00'),
            'updated_at' => CarbonImmutable::now()->subDays(40),
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 1,
        ]);
    }

    public function test_最後の公開レッスンを当日に完了したチャプターは当日の完了したチャプター件数に含める(): void
    {
        // Arrange — 1つ目を前日、最後の公開レッスンを当日に完了
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);

        $lesson1 = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $lesson2 = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => CarbonImmutable::parse('2026-09-16 09:00:00'),
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson2->id,
            'completed_at' => CarbonImmutable::parse('2026-09-17 09:00:00'),
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 1,
        ]);
    }

    public function test_すべての公開レッスンを前日までに完了したチャプターは当日の完了したチャプター件数に含めない(): void
    {
        // Arrange — すべての公開レッスンを前日までに完了
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
            'status' => ChapterStatusEnum::PUBLIC->value,
        ]);

        $lesson1 = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $lesson2 = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson1->id,
            'completed_at' => CarbonImmutable::parse('2026-09-15 09:00:00'),
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson2->id,
            'completed_at' => CarbonImmutable::parse('2026-09-16 09:00:00'),
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 0,
        ]);
    }

    public function test_完了日時が当日0時0分0秒以降のレッスンだけを当日の完了したレッスン件数に含める(): void
    {
        // Arrange — 前日23時59分59秒に完了したレッスンと、当日0時0分0秒に完了したレッスン
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);
        $lessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[0]->id,
            'completed_at' => CarbonImmutable::parse('2026-09-16 23:59:59'),
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[1]->id,
            'completed_at' => CarbonImmutable::parse('2026-09-17 00:00:00'),
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 1,
        ]);
    }

    public function test_完了日時が当月1日0時0分0秒以降のレッスンだけを当月の完了したレッスン件数に含める(): void
    {
        // Arrange — 前月末日23時59分59秒に完了したレッスンと、当月1日0時0分0秒に完了したレッスン
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);
        $lessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[0]->id,
            'completed_at' => CarbonImmutable::parse('2026-08-31 23:59:59'),
        ]);

        LessonAttendance::factory()->completed()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[1]->id,
            'completed_at' => CarbonImmutable::parse('2026-09-01 00:00:00'),
        ]);

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 1,
        ]);
    }

    public function test_最後のレッスンの完了が当日0時0分0秒以降のチャプターだけを当日の完了したチャプター件数に含める(): void
    {
        // Arrange — 最後のレッスンの完了が前日23時59分59秒のチャプターと、当日0時0分0秒のチャプター
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);

        $chapterBefore = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);
        $lessonsBefore = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapterBefore->id,
        ]);

        $chapterAfter = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);
        $lessonsAfter = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapterAfter->id,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        $completedAtByLesson = [
            [$lessonsBefore[0], '2026-09-16 10:00:00'],
            [$lessonsBefore[1], '2026-09-16 23:59:59'],
            [$lessonsAfter[0], '2026-09-16 10:00:00'],
            [$lessonsAfter[1], '2026-09-17 00:00:00'],
        ];

        foreach ($completedAtByLesson as [$lesson, $completedAt]) {
            LessonAttendance::factory()->completed()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'completed_at' => CarbonImmutable::parse($completedAt),
            ]);
        }

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 1,
        ]);
    }

    public function test_すべてのレッスンを前月に完了したチャプターは当月に更新されても当月の完了したチャプター件数に含めない(): void
    {
        // Arrange — 前月にすべて完了し、受講状況は当日に更新されている
        $manager = Instructor::factory()->create();
        $course = Course::factory()->create([
            'instructor_id' => $manager->id,
        ]);
        $chapter = Chapter::factory()->create([
            'course_id' => $course->id,
        ]);
        $lessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
        ]);

        $attendance = Attendance::factory()->create([
            'course_id' => $course->id,
        ]);

        foreach ($lessons as $lesson) {
            LessonAttendance::factory()->completed()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'completed_at' => CarbonImmutable::parse('2026-08-20 10:00:00'),
                'updated_at' => CarbonImmutable::now(),
            ]);
        }

        $this->actingAs($manager, 'instructor');

        // Act
        $response = $this->getJson(route('manager.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'completed_chapters_count' => 0,
        ]);
    }
}
