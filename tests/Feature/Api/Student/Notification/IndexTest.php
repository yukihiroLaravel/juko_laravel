<?php

namespace Tests\Feature\Api\Student\Notification;

use App\Enums\Course\DeadlineTypeEnum;
use App\Enums\Notification\StatusEnum;
use App\Enums\Notification\TypeEnum;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Instructor;
use App\Model\Notification;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    /** AC-NOTIF-014 */
    public function test_期限がない講座のお知らせは取得する(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = $this->createEnrolledCourse($student);
        $this->createDisplayableNotification($course);
        $this->createDisplayableNotification($course);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.notifications');
    }

    /** AC-NOTIF-014 */
    #[DataProvider('deadlineTypeProvider')]
    public function test_受講期限を過ぎた講座のお知らせは取得しない(string $deadlineType): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = $this->createEnrolledCourse(
            $student,
            courseAttributes: ['deadline_type' => $deadlineType],
            attendanceAttributes: ['attendance_deadline' => now()->subDay()],
        );
        $this->createDisplayableNotification($course);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data.notifications');
    }

    /** @return array<string, array{string}> */
    public static function deadlineTypeProvider(): array
    {
        return [
            '固定日' => [DeadlineTypeEnum::FIXED_DATE->value],
            '相対日数' => [DeadlineTypeEnum::RELATIVE_DAYS->value],
        ];
    }

    /** AC-NOTIF-018 */
    public function test_お知らせ一覧に講師名が含まれる(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $instructor = Instructor::factory()->create(['nick_name' => 'Alice']);
        $course = $this->createEnrolledCourse($student);
        $this->createDisplayableNotification($course, ['instructor_id' => $instructor->id]);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.notifications.0.instructor_nick_name', 'Alice');
    }

    /**
     * AC-NOTIF-015
     *
     * @param  list<string>  $createdNickNames
     * @param  list<string>  $expectedNickNames
     */
    #[DataProvider('instructorNickNameSortProvider')]
    public function test_講師名でソートできる(string $order, array $createdNickNames, array $expectedNickNames): void
    {
        // Arrange — 作成順と期待する並びを逆にし、作成順のまま返ると失敗するようにする
        $student = Student::factory()->create();
        $course = $this->createEnrolledCourse($student);
        foreach ($createdNickNames as $nickName) {
            $instructor = Instructor::factory()->create(['nick_name' => $nickName]);
            $this->createDisplayableNotification($course, ['instructor_id' => $instructor->id]);
        }
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.index', [
            'sort_by' => 'instructor_nick_name',
            'order' => $order,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.notifications.0.instructor_nick_name', $expectedNickNames[0]);
        $response->assertJsonPath('data.notifications.1.instructor_nick_name', $expectedNickNames[1]);
    }

    /** @return array<string, array{string, list<string>, list<string>}> */
    public static function instructorNickNameSortProvider(): array
    {
        return [
            '昇順' => ['asc', ['Bob', 'Alice'], ['Alice', 'Bob']],
            '降順' => ['desc', ['Alice', 'Bob'], ['Bob', 'Alice']],
        ];
    }

    // AC-NOTIF-030
    public function test_一度きりのお知らせは既読と未読を返し常時表示は対象外になる(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = $this->createEnrolledCourse($student);
        $readNotification = $this->createDisplayableNotification($course, ['type' => TypeEnum::ONCE]);
        $unreadNotification = $this->createDisplayableNotification($course, ['type' => TypeEnum::ONCE]);
        $alwaysNotification = $this->createDisplayableNotification($course, ['type' => TypeEnum::ALWAYS]);
        $readNotification->students()->attach($student->id);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'notification_id' => $readNotification->id,
            'read_status' => 'read',
        ]);
        $response->assertJsonFragment([
            'notification_id' => $unreadNotification->id,
            'read_status' => 'unread',
        ]);
        $response->assertJsonFragment([
            'notification_id' => $alwaysNotification->id,
            'read_status' => null,
        ]);
    }

    // AC-NOTIF-030
    public function test_既読状態の昇順と降順で未読と既読の順序が切り替わり常時表示は最後になる(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = $this->createEnrolledCourse($student);
        $readNotification = $this->createDisplayableNotification($course, ['type' => TypeEnum::ONCE]);
        $unreadNotification = $this->createDisplayableNotification($course, ['type' => TypeEnum::ONCE]);
        $alwaysNotification = $this->createDisplayableNotification($course, ['type' => TypeEnum::ALWAYS]);
        $readNotification->students()->attach($student->id);
        $this->actingAs($student, 'web');

        // Act
        $ascendingResponse = $this->getJson(route('student.notifications.index', [
            'sort_by' => 'read_status',
            'order' => 'asc',
        ]));
        $descendingResponse = $this->getJson(route('student.notifications.index', [
            'sort_by' => 'read_status',
            'order' => 'desc',
        ]));

        // Assert
        $ascendingResponse->assertStatus(200);
        $ascendingResponse->assertJsonPath('data.notifications.0.notification_id', $unreadNotification->id);
        $ascendingResponse->assertJsonPath('data.notifications.1.notification_id', $readNotification->id);
        $ascendingResponse->assertJsonPath('data.notifications.2.notification_id', $alwaysNotification->id);
        $descendingResponse->assertStatus(200);
        $descendingResponse->assertJsonPath('data.notifications.0.notification_id', $readNotification->id);
        $descendingResponse->assertJsonPath('data.notifications.1.notification_id', $unreadNotification->id);
        $descendingResponse->assertJsonPath('data.notifications.2.notification_id', $alwaysNotification->id);
    }

    public function test_不正なソート項目はバリデーションエラーになる(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.index', [
            'sort_by' => 'invalid_sort_key',
        ]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sort_by']);
    }

    // AC-NOTIF-017
    #[DataProvider('filterProvider')]
    public function test_既読・未読の絞り込み条件を指定してもエラーにならずお知らせが全件返る(string $filter): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = $this->createEnrolledCourse($student);
        $readNotification = $this->createDisplayableNotification($course, ['type' => TypeEnum::ONCE]);
        $unreadNotification = $this->createDisplayableNotification($course, ['type' => TypeEnum::ONCE]);
        $readNotification->students()->attach($student->id);
        $this->actingAs($student, 'web');

        // Act
        $response = $this->getJson(route('student.notifications.index', [
            'filter' => $filter,
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data.notifications');
        $response->assertJsonFragment(['notification_id' => $readNotification->id]);
        $response->assertJsonFragment(['notification_id' => $unreadNotification->id]);
    }

    /** @return array<string, array{string}> */
    public static function filterProvider(): array
    {
        return [
            '既読' => ['read'],
            '未読' => ['unread'],
            '決められていない値' => ['invalid_filter'],
        ];
    }

    /**
     * 生徒が受講している講座を作る。受講期限は既定でなし。
     *
     * @param  array<string, mixed>  $courseAttributes
     * @param  array<string, mixed>  $attendanceAttributes
     */
    private function createEnrolledCourse(Student $student, array $courseAttributes = [], array $attendanceAttributes = []): Course
    {
        $course = Course::factory()->create($courseAttributes);
        Attendance::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            ...$attendanceAttributes,
        ]);

        return $course;
    }

    /**
     * 現在掲示中の公開お知らせを作る。start_date の既定は乱数なので、掲示開始を昨日に固定する。
     *
     * @param  array<string, mixed>  $attributes
     */
    private function createDisplayableNotification(Course $course, array $attributes = []): Notification
    {
        return Notification::factory()->create([
            'course_id' => $course->id,
            'status' => StatusEnum::PUBLIC,
            'start_date' => now()->subDay(),
            ...$attributes,
        ]);
    }
}
