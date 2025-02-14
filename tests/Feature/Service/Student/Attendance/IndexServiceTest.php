<?php

namespace Tests\Feature\Service\Student\Attendance;

use App\Dto\Student\Attendance\IndexDto;
use App\Model\Attendance;
use App\Model\Course;
use App\Model\Student;
use App\Services\Student\Attendance\IndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class IndexServiceTest extends TestCase
{
    use RefreshDatabase;

    // setup
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 「attendances」に調整用の追加情報を投入する。
     */
    protected function ajustInsertAttendance()
    {
        /*
            ＜当メソッドの定義に至った背景の説明＞

                テストコードでは厳密性の高いテストは実施しない方針となった。
                そのため、
                データベースシーダーの内容をコピーしてテスト用のシーダーを作成し、
                テスト用のシーダーを別個に成長させ、
                データベースシーダーの変更で、テストコードが影響を受けないことを極度に気にする必要性がなくなりました。

                テストコードは、大枠の構造的なassertにとどめるため
                データベースシーダーの内容を利用する方針となった。

                データベースシーダーの内容でSQLiteへ反映させるイメージで、
                「$this->seed();」を実施後に、
                各テスト毎に、必要であれば、データの補完などの調整をSQLiteに対して行う方針となった。

                上記の前提がまずあって、postmanでの動作テストを行っていた時の話ですが、
                2024/11/02時点、
                app/Http/Controllers/Api/Student/AttendanceController.php
                のindex()の
                検索条件なしでの結果、検索条件ありでの結果について、
                IndexServiceを使った実装変更後と、実装変更前とで、レスポンスの結果を比較するテストを実施時に
                gitにある、database/seeds/AttendanceSeeder.phpでは、
                件数が少なすぎて、テストがしにくかった。
                検索条件なしの段階より、結果が1件の状況だと、
                検索条件ありの段階で絞り込まれて結果が表示されたことが結果を見てわかりにくい状況だった。

                そのため、実装変更後の環境と、実装変更前の環境と、ともに、
                下記のコードを、ローカルの開発環境だけで、
                database/seeds/AttendanceSeeder.php側に追記し、
                php artisan migrate:fresh --seed
                をしたうえで、実装変更後と、実装変更前とで、レスポンスの結果を比較で一致を確認しております。

                postmanでのテスト終了後に、
                database/seeds/AttendanceSeeder.phpでは、
                追加コードを削除しております。

                当IndexServiceTestでは、上記のpostmanでのテスト時と同様に、
                database/seeds/AttendanceSeeder.phpでの
                追加コードがあるイメージでのテストを実施したい。

                それを、
                検索条件なしでのテスト
                検索条件ありでのテスト
                の両方で、使いたい意図があり

                当メソッドを定義しております。
        */
        Attendance::insert([
            [
                'course_id' => 3,
                'student_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 5,
                'student_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 7,
                'student_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    public function test_正常系_受講中の講座一覧を取得する_検索条件なし()
    {
        // 「attendances」に調整用の追加情報を投入する。
        $this->ajustInsertAttendance();

        // arrange
        /** @var Student $student */
        $student = Student::find(1);
        $indexDto = new IndexDto($student->id, null);
        $service = new IndexService;
        // act
        $attendances = $service($indexDto);
        // assert
        // 戻り値がIlluminate\Database\Eloquent\Collectionであること
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $attendances);

        /*
            検索条件なし での件数確認

            SELECT
            COUNT(*) CNT
            FROM
            attendances
            WHERE
            student_id = 1

            -------
            CNT
            4
            -------
        */
        $this->assertCount(4, $attendances);

        foreach ($attendances as $attendance) {
            // 要素がAttendanceであること
            $this->assertInstanceOf(Attendance::class, $attendance);

            // courseのstatusがpublicであること
            $this->assertEquals(Course::STATUS_PUBLIC, $attendance->course->status);
        }
    }

    public function test_正常系_受講中の講座一覧を取得する_検索条件あり()
    {
        // 「attendances」に調整用の追加情報を投入する。
        $this->ajustInsertAttendance();

        // arrange
        /** @var Student $student */
        $student = Student::find(1);
        $searchWord = 'ython';
        $indexDto = new IndexDto($student->id, $searchWord);
        $service = new IndexService;
        // act
        $attendances = $service($indexDto);
        // assert
        // 戻り値がIlluminate\Database\Eloquent\Collectionであること
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $attendances);

        /*
            検索条件あり での件数確認

            SELECT
            COUNT(*) CNT
            FROM
            attendances ad
            INNER JOIN courses cs
            ON
            ad.course_id = cs.id
            WHERE
            ad.student_id = 1
            AND cs.title LIKE '%ython%'

            -------
            CNT
            1
            -------
        */
        $this->assertCount(1, $attendances);

        foreach ($attendances as $attendance) {
            // 要素がAttendanceであること
            $this->assertInstanceOf(Attendance::class, $attendance);

            // courseのstatusがpublicであること
            $this->assertEquals(Course::STATUS_PUBLIC, $attendance->course->status);

            // courseのtitleが「$indexDto->getSearchWord()」を含んだ文字列であること
            $this->assertStringContainsString($indexDto->getSearchWord(), $attendance->course->title);
        }
    }
}
