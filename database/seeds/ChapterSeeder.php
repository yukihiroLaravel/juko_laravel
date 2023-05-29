<?php

use App\Model\Chapter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ChapterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Chapter::insert([
            [
                'course_id' => 1,
                'order' => 1,
                'title' => 'PHPとは？',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 1,
                'order' => 2,
                'title' => 'PHPの基礎を学ぼう',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 1,
                'order' => 3,
                'title' => 'PHPの応用にチャレンジしよう',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
