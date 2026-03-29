<?php

namespace Tests\Feature\Api\Student\Attendance;

use App\Model\Attendance;
use App\Model\Chapter;
use App\Model\Course;
use App\Model\Lesson;
use App\Model\LessonAttendance;
use App\Model\Student;
use App\Model\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_受講一覧を取得_成功(): void
    {
        // Arrange — 3つの公開講座にチャプター・レッスン・レッスン受講を含む受講を作成
        $student = Student::factory()->create();
        $courses = Course::factory()->count(3)->create(['status' => Course::STATUS_PUBLIC]);
        foreach ($courses as $course) {
            $chapter = Chapter::factory()->create(['course_id' => $course->id]);
            $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
            $attendance = Attendance::factory()->create([
                'student_id' => $student->id,
                'course_id' => $course->id,
            ]);
            LessonAttendance::factory()->create([
                'attendance_id' => $attendance->id,
                'lesson_id' => $lesson->id,
                'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
            ]);
        }
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'attendance_id',
                    'course' => [
                        'course_id',
                        'title',
                        'continue_from',
                        'tags',
                        'instructor',
                    ],
                ],
            ],
            'meta' => [
                'total',
                'per_page',
                'current_page',
                'last_page',
            ],
        ]);
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
        $response = $this->getJson(route('student.attendances.index', ['tag_id' => $tag->id]));

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
        $response = $this->getJson(route('student.attendances.index', ['search_word' => 'バックエンド']));

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
        $response = $this->getJson(route('student.attendances.index', ['search_word' => 'Vue']));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_未ログインの場合はエラーを返す(): void
    {
        // Act
        $response = $this->getJson(route('student.attendances.index'));

        // Assert
        $response->assertStatus(401);
    }

    public function test_自分の受講のみ取得される(): void
    {
        // Arrange — 2人の生徒がそれぞれ受講を持つ
        $student1 = Student::factory()->create();
        $student2 = Student::factory()->create();
        $course1 = Course::factory()->create(['status' => Course::STATUS_PUBLIC]);
        $course2 = Course::factory()->create(['status' => Course::STATUS_PUBLIC]);
        Attendance::factory()->create(['student_id' => $student1->id, 'course_id' => $course1->id]);
        Attendance::factory()->create(['student_id' => $student2->id, 'course_id' => $course2->id]);
        $this->actingAs($student1);

        // Act
        $response = $this->getJson(route('student.attendances.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_非公開の講座は一覧に表示されない(): void
    {
        // Arrange — 公開1つ、非公開1つ
        $student = Student::factory()->create();
        $publicCourse = Course::factory()->create(['status' => Course::STATUS_PUBLIC]);
        $privateCourse = Course::factory()->create(['status' => Course::STATUS_PRIVATE]);
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $publicCourse->id]);
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $privateCourse->id]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_受講がない場合は空の一覧を返す(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(0, 'data');
    }

    public function test_ページ指定で件数を区切って取得できる(): void
    {
        // Arrange — 5つの受講を作成し、per_page=2で取得
        $student = Student::factory()->create();
        $courses = Course::factory()->count(5)->create(['status' => Course::STATUS_PUBLIC]);
        foreach ($courses as $course) {
            Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        }
        $this->actingAs($student);

        // Act — 1ページ目
        $response = $this->getJson(route('student.attendances.index', ['per_page' => 2, 'page' => 1]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.total', 5);
        $response->assertJsonPath('meta.last_page', 3);

        // Act — 3ページ目（残り1件）
        $response = $this->getJson(route('student.attendances.index', ['per_page' => 2, 'page' => 3]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_存在しないタグを指定するとエラーを返す(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.index', ['tag_id' => 99999]));

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tag_id');
    }

    public function test_続きから_未完了のレッスンがある場合は最初の未完了レッスン情報を返す(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create(['status' => Course::STATUS_PUBLIC]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 1]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter->id, 'order' => 2]);
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson1->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson2->id,
            'status' => LessonAttendance::STATUS_BEFORE_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.course.continue_from.chapter_id', $chapter->id);
        $response->assertJsonPath('data.0.course.continue_from.lesson_id', $lesson2->id);
        $response->assertJsonPath('data.0.course.continue_from.chapter_title', $chapter->title);
        $response->assertJsonPath('data.0.course.continue_from.lesson_title', $lesson2->title);
    }

    public function test_続きから_全レッスン完了の場合は続きから情報がない(): void
    {
        // Arrange
        $student = Student::factory()->create();
        $course = Course::factory()->create(['status' => Course::STATUS_PUBLIC]);
        $chapter = Chapter::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->create(['chapter_id' => $chapter->id]);
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.course.continue_from', null);
    }

    public function test_続きから_最初のチャプター完了済みなら次のチャプターから再開する(): void
    {
        // Arrange — 1つ目のチャプターは完了、2つ目のチャプターに未完了レッスンがある
        $student = Student::factory()->create();
        $course = Course::factory()->create(['status' => Course::STATUS_PUBLIC]);
        $chapter1 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 1]);
        $chapter2 = Chapter::factory()->create(['course_id' => $course->id, 'order' => 2]);
        $lesson1 = Lesson::factory()->create(['chapter_id' => $chapter1->id, 'order' => 1]);
        $lesson2 = Lesson::factory()->create(['chapter_id' => $chapter2->id, 'order' => 1]);
        $attendance = Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson1->id,
            'status' => LessonAttendance::STATUS_COMPLETED_ATTENDANCE,
        ]);
        LessonAttendance::factory()->create([
            'attendance_id' => $attendance->id,
            'lesson_id' => $lesson2->id,
            'status' => LessonAttendance::STATUS_IN_ATTENDANCE,
        ]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.course.continue_from.chapter_id', $chapter2->id);
        $response->assertJsonPath('data.0.course.continue_from.lesson_id', $lesson2->id);
    }

    public function test_続きから_レッスンがない講座では続きから情報がない(): void
    {
        // Arrange — チャプターはあるがレッスンがない講座
        $student = Student::factory()->create();
        $course = Course::factory()->create(['status' => Course::STATUS_PUBLIC]);
        Chapter::factory()->create(['course_id' => $course->id]);
        Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        $this->actingAs($student);

        // Act
        $response = $this->getJson(route('student.attendances.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonPath('data.0.course.continue_from', null);
    }

    public function test_タグ指定と検索ワードを同時に使って絞り込める(): void
    {
        // Arrange — タグ付き講座2つのうち、講座名が一致するのは1つだけ
        $student = Student::factory()->create();
        $tag = Tag::factory()->create(['content' => 'プログラミング']);
        $courseMatch = Course::factory()->create(['title' => 'PHP入門', 'status' => Course::STATUS_PUBLIC]);
        $courseMatch->tags()->attach($tag->id);
        $courseNoMatch = Course::factory()->create(['title' => 'デザイン基礎', 'status' => Course::STATUS_PUBLIC]);
        $courseNoMatch->tags()->attach($tag->id);
        foreach ([$courseMatch, $courseNoMatch] as $course) {
            Attendance::factory()->create(['student_id' => $student->id, 'course_id' => $course->id]);
        }
        $this->actingAs($student);

        // Act — tag_idとsearch_wordを併用
        $response = $this->getJson(route('student.attendances.index', [
            'tag_id' => $tag->id,
            'search_word' => 'PHP',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }
}
