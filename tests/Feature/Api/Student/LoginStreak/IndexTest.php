<?php

namespace Tests\Feature\Api\Student\LoginStreak;

use App\Model\Student;
use App\Model\StudentLoginHistory;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_連続ログイン日数取得_今日のみログイン_成功(): void
    {
        // Arrange
        $now = CarbonImmutable::create(2026, 4, 17);
        CarbonImmutable::setTestNow($now);

        $student = Student::factory()->create();
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login-streak'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['login_streak_days'],
        ]);
        $response->assertJsonPath('data.login_streak_days', 1);

        CarbonImmutable::setTestNow();
    }

    public function test_連続ログイン日数取得_複数日連続ログイン_成功(): void
    {
        // Arrange
        $now = CarbonImmutable::create(2026, 4, 17);
        CarbonImmutable::setTestNow($now);

        $student = Student::factory()->create();
        // 今日から5日連続でログイン
        foreach (range(0, 4) as $i) {
            StudentLoginHistory::factory()->create([
                'student_id' => $student->id,
                'logged_in_at' => $now->subDays($i),
            ]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login-streak'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.login_streak_days', 5);

        CarbonImmutable::setTestNow();
    }

    public function test_連続ログイン日数取得_今日未ログイン_null返却(): void
    {
        // Arrange
        $now = CarbonImmutable::create(2026, 4, 17);
        CarbonImmutable::setTestNow($now);

        $student = Student::factory()->create();
        // 昨日以前のログインはあるが、今日のログインはない
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now->subDay(),
        ]);
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now->subDays(2),
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login-streak'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.login_streak_days', null);

        CarbonImmutable::setTestNow();
    }

    public function test_連続ログイン日数取得_ログイン履歴なし_null返却(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login-streak'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.login_streak_days', null);
    }

    public function test_連続ログイン日数取得_連続が途切れた場合_途切れる前までカウント(): void
    {
        // Arrange
        $now = CarbonImmutable::create(2026, 4, 17);
        CarbonImmutable::setTestNow($now);

        $student = Student::factory()->create();
        // 今日・昨日はログインあり、2日前は飛ばして3日前・4日前にログインあり
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now,
        ]);
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now->subDay(),
        ]);
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now->subDays(3),
        ]);
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now->subDays(4),
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login-streak'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.login_streak_days', 2);

        CarbonImmutable::setTestNow();
    }

    public function test_連続ログイン日数取得_同日に複数回ログインしても1日としてカウント(): void
    {
        // Arrange
        $now = CarbonImmutable::create(2026, 4, 17, 20, 0, 0);
        CarbonImmutable::setTestNow($now);

        $student = Student::factory()->create();
        // 今日の中で複数回ログイン
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now->setTime(8, 0, 0),
        ]);
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now->setTime(12, 0, 0),
        ]);
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now->setTime(20, 0, 0),
        ]);
        // 昨日も1回ログイン
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

        CarbonImmutable::setTestNow();
    }

    public function test_連続ログイン日数取得_他の受講生のログイン履歴は影響しない(): void
    {
        // Arrange
        $now = CarbonImmutable::create(2026, 4, 17);
        CarbonImmutable::setTestNow($now);

        $student = Student::factory()->create();
        $otherStudent = Student::factory()->create();

        // 対象受講生は今日のみログイン
        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => $now,
        ]);
        // 他の受講生は連続5日ログイン（影響してはいけない）
        foreach (range(0, 4) as $i) {
            StudentLoginHistory::factory()->create([
                'student_id' => $otherStudent->id,
                'logged_in_at' => $now->subDays($i),
            ]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login-streak'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.login_streak_days', 1);

        CarbonImmutable::setTestNow();
    }

    public function test_未認証_連続ログイン日数取得_失敗(): void
    {
        // Act
        $response = $this->getJson(route('student.login-streak'));

        // Assert
        $response->assertStatus(401);
    }
}
