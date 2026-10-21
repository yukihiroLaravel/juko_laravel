<?php

namespace Tests\Feature\Api\Student\LoginStreak;

use App\Model\Student;
use App\Model\StudentLoginHistory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 4, 17));
    }

    #[\Override]
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    /**
     * AC-ANALYTICS-039, AC-ANALYTICS-041
     *
     * @param  list<int>  $loggedInDaysAgo  今日から数えて何日前にログインしたか
     */
    #[DataProvider('loginStreakProvider')]
    public function test_連続ログイン日数は今日から途切れずに遡れた日数になる(array $loggedInDaysAgo, ?int $expectedDays): void
    {
        // Arrange
        $student = Student::factory()->create();
        foreach ($loggedInDaysAgo as $daysAgo) {
            StudentLoginHistory::factory()->create([
                'student_id' => $student->id,
                'logged_in_at' => CarbonImmutable::now()->subDays($daysAgo),
            ]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login-streak'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.login_streak_days', $expectedDays);
    }

    /** @return array<string, array{list<int>, ?int}> */
    public static function loginStreakProvider(): array
    {
        return [
            '今日だけログイン' => [[0], 1],
            '今日から5日連続でログイン' => [[0, 1, 2, 3, 4], 5],
            '2日前を飛ばして3日前と4日前にもログイン' => [[0, 1, 3, 4], 2],
            '昨日以前のログインはあるが今日はない' => [[1, 2], null],
            'ログイン履歴がない' => [[], null],
        ];
    }

    /** AC-ANALYTICS-040 */
    public function test_同じ日に複数回ログインしても1日として数える(): void
    {
        // Arrange
        $now = CarbonImmutable::create(2026, 4, 17, 20, 0, 0);
        CarbonImmutable::setTestNow($now);

        $student = Student::factory()->create();
        foreach ([8, 12, 20] as $hour) {
            StudentLoginHistory::factory()->create([
                'student_id' => $student->id,
                'logged_in_at' => $now->setTime($hour, 0, 0),
            ]);
        }
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now->subDay(),
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login-streak'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.login_streak_days', 2);
    }

    /** AC-ANALYTICS-042 */
    public function test_他の受講生のログイン履歴は連続ログイン日数に影響しない(): void
    {
        // Arrange
        $now = CarbonImmutable::now();
        $student = Student::factory()->create();
        $otherStudent = Student::factory()->create();

        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now,
        ]);
        // 他の受講生は5日連続でログインしている
        foreach (range(0, 4) as $daysAgo) {
            StudentLoginHistory::factory()->create([
                'student_id' => $otherStudent->id,
                'logged_in_at' => $now->subDays($daysAgo),
            ]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login-streak'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.login_streak_days', 1);
    }

    public function test_未ログインの場合は連続ログイン日数を取得できない(): void
    {
        // Act
        $response = $this->getJson(route('student.login-streak'));

        // Assert
        $response->assertStatus(401);
    }
}
