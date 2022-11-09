<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Model\Lesson;

class LessonsSeeder extends Seeder
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
               'url' => 'http://www.youtube.com',
               'title' => 'PHPとは',
               'remarks' => '動画index',
               'status' => 10,
               'created_at' => Carbon::now(),
               'updated_at' => Carbon::now(),
           ],
           [
            'chapter_id' => 2,
            'url' => 'http://www.youtube.com',
            'title' => 'コードを書いてみよう！',
            'remarks' => '動画index',
            'status' => 10,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ]
           ]);
    }
}
