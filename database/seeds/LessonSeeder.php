<?php

use App\Model\Lesson;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Lesson::insert([
            [
                'chapter_id' => 1,
                'url' => 'sVbEyFZKgqk',
                'title' => 'echo',
                'remarks' => "HTMLでは決められたテキストしか表示することができませんでした。\nPHPを使うと、見る人や状況に応じて、表示するテキストを変えることができます。",
                'status' => Lesson::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'order'=> 1,
            ],
            [
                'chapter_id' => 2,
                'url' => 'KgUp3FomMoc',
                'title' => 'データの種類',
                'remarks' => "PHPには、「文字列」や「数値」などのデータの種類があります。\n「'Hello'」,「'a'」などは文字列、「1」,「3.14」などは数値となります。",
                'status' => Lesson::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'order'=> 1,
            ],
            [
                'chapter_id' => 2,
                'url' => 'HrtS-FkPBqk',
                'title' => '変数',
                'remarks' => "プログラミングの重要な概念の1つである「変数」を学びましょう。\n変数とは、データの入れ物です。\n頭に「\$」記号をつけることによって変数を定義します。\n「\$変数名 = 値;」で様々な値を変数に入れることが出来ます。\n「＝」はプログラミングの世界では、右辺を左辺に代入するという意味です。",
                'status' => Lesson::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'order'=> 2,
            ],
            [
                'chapter_id' => 2,
                'url' => '6JtP8xk1U_k',
                'title' => '変数の値を更新する',
                'remarks' => "変数は、中に入っている値を変更することもできます。\n変数に、その後再び値を代入すると、後で代入した値によって変数の中身が上書きされます。",
                'status' => Lesson::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'order'=> 3,
            ],
            [
                'chapter_id' => 2,
                'url' => 'KH4MmQsCDuw',
                'title' => '文字列の連結',
                'remarks' => "ドット「.」記号を用いると文字列を連結することが出来ます。\n文字列同士の連結、変数と文字列の連結、変数同士の連結をすることができます。",
                'status' => Lesson::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'order'=> 4,
            ],
            [
                'chapter_id' => 3,
                'url' => 'KH4MmQsCDuw',
                'title' => '環境構築',
                'remarks' => "",
                'status' => Lesson::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'order'=> 1,
            ],
        ]);
    }
}
