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

        $this->actingAs($student, 'web'); // Sanctum認証
        
        // Act
        $response = $this->getJson(route('student.login_histories', [
            'start_date' => '2026-02-01',
            'end_date'   => '2026-03-01',
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
}