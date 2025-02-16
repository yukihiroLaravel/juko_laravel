<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_index_is_paginated()
    {
        // テスト用データ作成（18件）
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        Attendance::factory()->count(18)->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
        ]);

        // APIをリクエスト（1ページ目を取得）
        $response = $this->actingAs($student)->getJson('/api/v1/attendance/index?per_page=6&page=1');
        $response->assertStatus(200);

        // JSONの構造をチェック
        $response->assertJsonStructure([
            'data',
            'links' => [
                'first', 'last', 'prev', 'next',
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total',
            ],
        ]);

        // 1ページのデータ件すが6件か確認
        $response->assertJsonCount(6, 'data');

        // current_pageが1であることを確認
        $this->assertEquals(1, $response->json('meta.current_page'));

        // last_pageが3であることを確認
        $this->assertEquals(3, $response->json('meta.last_page'));
    }
}
