<?php

namespace Tests\Feature\Api\Student\LoginHistory;

use App\Model\Student;
use App\Model\StudentLoginHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_クエリパラメータを指定してログイン履歴を取得できること(): void
    {
        // Arrange — 期間内3件・期間外2件のログイン履歴を持つ受講生
        $student = Student::factory()->create();

        StudentLoginHistory::factory()->count(3)->create([
            'student_id' => $student->id,
            'logged_in_at' => '2026-02-15 10:00:00',
        ]);

        StudentLoginHistory::factory()->count(2)->create([
            'student_id' => $student->id,
            'logged_in_at' => '2026-01-01 10:00:00',
        ]);

        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login_histories', [
            'start_date' => '2026-02-01',
            'end_date' => '2026-03-01',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['login_count' => 3]);
        $response->assertJsonCount(3, 'login_histories');
        $response->assertJsonStructure([
            'login_count',
            'login_histories' => [
                '*' => [
                    'student_login_history_id',
                    'logged_in_at',
                ],
            ],
        ]);
    }

    public function test_未認証の場合は401が返ること(): void
    {
        // Act
        $response = $this->getJson(route('student.login_histories'));

        // Assert
        $response->assertStatus(401);
    }

    public function test_クエリパラメータなしで全件取得できること(): void
    {
        // Arrange
        $student = Student::factory()->create();

        StudentLoginHistory::factory()->count(5)->create([
            'student_id' => $student->id,
        ]);

        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login_histories'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['login_count' => 5]);
        $response->assertJsonCount(5, 'login_histories');
    }

    public function test_該当データなしで空配列と0件が返ること(): void
    {
        // Arrange
        $student = Student::factory()->create();

        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login_histories', [
            'start_date' => '2026-02-01',
            'end_date' => '2026-03-01',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['login_count' => 0]);
        $response->assertJsonCount(0, 'login_histories');
    }

    public function test_他の生徒のログイン履歴が含まれないこと(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $otherStudent = Student::factory()->create();

        StudentLoginHistory::factory()->count(2)->create([
            'student_id' => $student->id,
            'logged_in_at' => '2026-02-15 10:00:00',
        ]);

        StudentLoginHistory::factory()->count(3)->create([
            'student_id' => $otherStudent->id,
            'logged_in_at' => '2026-02-15 10:00:00',
        ]);

        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login_histories'));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['login_count' => 2]);
        $response->assertJsonCount(2, 'login_histories');
    }

    public function test_開始日のみ指定した場合にそれ以降の履歴が返ること(): void
    {
        // Arrange
        $student = Student::factory()->create();

        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => '2026-01-01 10:00:00',
        ]);

        StudentLoginHistory::factory()->count(2)->create([
            'student_id' => $student->id,
            'logged_in_at' => '2026-03-01 10:00:00',
        ]);

        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login_histories', [
            'start_date' => '2026-02-01',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['login_count' => 2]);
        $response->assertJsonCount(2, 'login_histories');
    }

    public function test_終了日のみ指定した場合にそれ以前の履歴が返ること(): void
    {
        // Arrange
        $student = Student::factory()->create();

        StudentLoginHistory::factory()->count(2)->create([
            'student_id' => $student->id,
            'logged_in_at' => '2026-01-15 10:00:00',
        ]);

        StudentLoginHistory::factory()->create([
            'student_id' => $student->id,
            'logged_in_at' => '2026-03-15 10:00:00',
        ]);

        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login_histories', [
            'end_date' => '2026-02-01',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['login_count' => 2]);
        $response->assertJsonCount(2, 'login_histories');
    }

    public function test_不正な日付フォーマットの場合は422が返ること(): void
    {
        // Arrange
        $student = Student::factory()->create();

        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login_histories', [
            'start_date' => '2026/02/01',
            'end_date' => '2026/03/01',
        ]));

        // Assert
        $response->assertStatus(422);
    }

    public function test_終了日が開始日より前の場合は422が返ること(): void
    {
        // Arrange
        $student = Student::factory()->create();

        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.login_histories', [
            'start_date' => '2026-03-01',
            'end_date' => '2026-02-01',
        ]));

        // Assert
        $response->assertStatus(422);
    }
}
