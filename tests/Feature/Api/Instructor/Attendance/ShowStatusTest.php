<?php

namespace Tests\Feature\Api\Instructor\Attendance;

use App\Enums\Lesson\StatusEnum as LessonStatusEnum;
use App\Enums\LessonAttendance\StatusEnum as LessonAttendanceStatusEnum;
use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_当日の出席状況を取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_今月の出席状況を取得_成功(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'month',
        ]));

        // Assert
        $response->assertStatus(200);
    }

    public function test_無効のパラメータ(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => 99999,
            'period' => 'invalid',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'course_id',
            'period',
        ]);
    }

    public function test_権限のない講師_失敗(): void
    {
        // Arrange — 他の講師の講座を指定
        $ownerInstructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $ownerInstructor->id]);
        $otherInstructor = Instructor::factory()->create();
        $this->actingAs($otherInstructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(403);
    }

    public function test_受講生がいない場合_平均進捗率は0(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['average_progress_rate' => 0]);
    }

    public function test_レッスンが0件の場合_平均進捗率は0(): void
    {
        // Arrange — 講座にチャプター/レッスンが無く、受講生のみ存在
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $student = Student::factory()->create();
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['average_progress_rate' => 0]);
    }

    public function test_非公開レッスンは平均進捗率の計算に含まれない(): void
    {
        // Arrange — 公開2本 + 非公開2本、受講生1名が公開レッスン2本を完了
        // 母数 = 公開Lesson 2本 × 受講生1名 = 2、完了 = 2 → 100%
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);

        $publicLessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $privateLessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        // 公開Lesson 2件を完了
        foreach ($publicLessons as $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            ]);
        }
        // 非公開Lesson はステータス遷移しないものの、レコード自体は存在する想定
        foreach ($privateLessons as $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
            ]);
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert — 非公開Lessonは母数・分子から除外され、completed: 2 / (1 × 2) × 100 = 100
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 2,
            'average_progress_rate' => 100,
        ]);
    }

    public function test_平均進捗率の計算が正しい(): void
    {
        // Arrange — 受講生2名 × レッスン4本、当日完了が5件 → floor(5 / (2 * 4) * 100) = 62
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(4)->create(['chapter_id' => $chapter->id]);

        $studentA = Student::factory()->create();
        $attendanceA = Attendance::factory()->create(['student_id' => $studentA->id, 'course_id' => $course->id]);
        $studentB = Student::factory()->create();
        $attendanceB = Attendance::factory()->create(['student_id' => $studentB->id, 'course_id' => $course->id]);

        // 受講生A: 4レッスンのうち3件を完了
        foreach ($lessons as $i => $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendanceA->id,
                'lesson_id' => $lesson->id,
                'status' => $i < 3
                    ? LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE
                    : LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
            ]);
        }
        // 受講生B: 4レッスンのうち2件を完了
        foreach ($lessons as $i => $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendanceB->id,
                'lesson_id' => $lesson->id,
                'status' => $i < 2
                    ? LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE
                    : LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
            ]);
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert — completed: 5 / (students: 2 × lessons: 4) × 100 = 62.5 → floor → 62
        $response->assertStatus(200);
        $response->assertJson([
            'completed_lessons_count' => 5,
            'average_progress_rate' => 62,
        ]);
    }

    public function test_受講生がいない場合_修了率は0(): void
    {
        // Arrange
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completion_rate' => 0]);
    }

    public function test_全員未修了の場合_修了率は0(): void
    {
        // Arrange — 受講生2名、全員全公開レッスン未完了
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $studentA = Student::factory()->create();
        $attendanceA = Attendance::factory()->create(['student_id' => $studentA->id, 'course_id' => $course->id]);
        $studentB = Student::factory()->create();
        $attendanceB = Attendance::factory()->create(['student_id' => $studentB->id, 'course_id' => $course->id]);

        foreach ([$attendanceA, $attendanceB] as $attendance) {
            foreach ($lessons as $lesson) {
                LessonAttendance::factory()->create([
                    'attendance_id' => $attendance->id,
                    'lesson_id' => $lesson->id,
                    'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
                ]);
            }
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completion_rate' => 0]);
    }

    public function test_一部修了の場合_修了率の計算が正しい(): void
    {
        // Arrange — 受講生2名のうち1名が全公開レッスン完了、もう1名は一部のみ → floor(1/2*100) = 50
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        // 受講生A: 全公開レッスンを完了
        $studentA = Student::factory()->create();
        $attendanceA = Attendance::factory()->create(['student_id' => $studentA->id, 'course_id' => $course->id]);
        foreach ($lessons as $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendanceA->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
                'completed_at' => now(),
            ]);
        }

        // 受講生B: 1件のみ完了
        $studentB = Student::factory()->create();
        $attendanceB = Attendance::factory()->create(['student_id' => $studentB->id, 'course_id' => $course->id]);
        foreach ($lessons as $i => $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendanceB->id,
                'lesson_id' => $lesson->id,
                'status' => $i === 0
                    ? LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE
                    : LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
                'completed_at' => $i === 0 ? now() : null,
            ]);
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert — completed_students: 1 / students: 2 × 100 = 50
        $response->assertStatus(200);
        $response->assertJson(['completion_rate' => 50]);
    }

    public function test_全員修了の場合_修了率は100(): void
    {
        // Arrange — 受講生2名、全員が全公開レッスン完了
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $studentA = Student::factory()->create();
        $attendanceA = Attendance::factory()->create(['student_id' => $studentA->id, 'course_id' => $course->id]);
        $studentB = Student::factory()->create();
        $attendanceB = Attendance::factory()->create(['student_id' => $studentB->id, 'course_id' => $course->id]);

        foreach ([$attendanceA, $attendanceB] as $attendance) {
            foreach ($lessons as $lesson) {
                LessonAttendance::factory()->create([
                    'attendance_id' => $attendance->id,
                    'lesson_id' => $lesson->id,
                    'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
                    'completed_at' => now(),
                ]);
            }
        }

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['completion_rate' => 100]);
    }

    public function test_非公開レッスンは修了率の判定に含まれない(): void
    {
        // Arrange — 公開2本＋非公開1本、受講生が公開レッスン2本のみ完了 → 全公開レッスン完了扱い → 100
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);

        $publicLessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);
        $privateLesson = Lesson::factory()->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PRIVATE->value,
        ]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        // 公開レッスンを完了
        foreach ($publicLessons as $lesson) {
            LessonAttendance::factory()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
                'completed_at' => now(),
            ]);
        }
        // 非公開レッスンは未完了のままレコードは作成（仕様上、受講登録時に全レッスン分作成される）
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $privateLesson->id,
            'status' => LessonAttendanceStatusEnum::BEFORE_ATTENDANCE,
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert — 非公開レッスンは判定外なので、公開レッスンを全完了で修了扱い
        $response->assertStatus(200);
        $response->assertJson(['completion_rate' => 100]);
    }

    public function test_ステータスが未完了でも完了日時があれば修了率に含まれる(): void
    {
        // Arrange — 受講生が一度完了後に再学習でステータスが戻った状態を再現
        // completed_at は単調なので残ったまま → 修了扱い
        $instructor = Instructor::factory()->create();
        $course = Course::factory()->create(['instructor_id' => $instructor->id]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lessons = Lesson::factory()->count(2)->create([
            'chapter_id' => $chapter->id,
            'status' => LessonStatusEnum::PUBLIC->value,
        ]);

        $student = Student::factory()->create();
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);

        // 1件目: 完了済み
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[0]->id,
            'status' => LessonAttendanceStatusEnum::COMPLETED_ATTENDANCE,
            'completed_at' => now(),
        ]);
        // 2件目: 過去に完了したが再学習でステータスが in_attendance に戻った状態（completed_at は残る）
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lessons[1]->id,
            'status' => LessonAttendanceStatusEnum::IN_ATTENDANCE,
            'completed_at' => now()->subDay(),
        ]);

        $this->actingAs($instructor, 'instructor');

        // Act
        $response = $this->getJson(route('instructor.courses.attendances.show-status', [
            'course_id' => $course->id,
            'period' => 'today',
        ]));

        // Assert — 完了日時が両方設定されているので全公開レッスン修了扱い
        $response->assertStatus(200);
        $response->assertJson(['completion_rate' => 100]);
    }
}
