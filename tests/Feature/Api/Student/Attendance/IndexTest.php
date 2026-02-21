<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Model\Attendance;
use App\Model\Course;
use App\Model\Student;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講一覧を取得_成功(): void
    {
        // Arrange — 3つの公開講座に受講を作成
        $student = Student::factory()->create();
        $courses = Course::factory()->count(3)->create(['status' => Course::STATUS_PUBLIC]);
        foreach ($courses as $course) {
            Attendance::factory()->create([
                'student_id' => $student->id,
                'course_id' => $course->id,
            ]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendance.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_受講一覧を取得_タグ指定_成功(): void
    {
        // Arrange — 1つの講座にタグを紐付け、もう1つはタグなし
        $student = Student::factory()->create();
        $tag = Tag::factory()->create();
        $courseWithTag = Course::factory()->create(['status' => Course::STATUS_PUBLIC]);
        $courseWithTag->tags()->attach($tag->id);
        $courseWithoutTag = Course::factory()->create(['status' => Course::STATUS_PUBLIC]);
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $courseWithTag->id]);
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $courseWithoutTag->id]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendance.index', ['tag_id' => $tag->id]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_タグ名で検索_成功(): void
    {
        // Arrange — タグ「バックエンド」を持つ講座2つ、持たない講座1つ
        $student = Student::factory()->create();
        $tag = Tag::factory()->create(['content' => 'バックエンド']);
        $course1 = Course::factory()->create(['status' => Course::STATUS_PUBLIC]);
        $course1->tags()->attach($tag->id);
        $course2 = Course::factory()->create(['status' => Course::STATUS_PUBLIC]);
        $course2->tags()->attach($tag->id);
        $course3 = Course::factory()->create(['status' => Course::STATUS_PUBLIC]);
        foreach ([$course1, $course2, $course3] as $course) {
            Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendance.index', ['search_word' => 'バックエンド']));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }

    public function test_講座名で検索_成功(): void
    {
        // Arrange — 「Vue入門」という講座1つ、他の講座2つ
        $student = Student::factory()->create();
        $courseVue = Course::factory()->create(['title' => 'Vue入門', 'status' => Course::STATUS_PUBLIC]);
        $courseOther1 = Course::factory()->create(['title' => 'Laravel基礎', 'status' => Course::STATUS_PUBLIC]);
        $courseOther2 = Course::factory()->create(['title' => 'React実践', 'status' => Course::STATUS_PUBLIC]);
        foreach ([$courseVue, $courseOther1, $courseOther2] as $course) {
            Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendance.index', ['search_word' => 'Vue']));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }
}
