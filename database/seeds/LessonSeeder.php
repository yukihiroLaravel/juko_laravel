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
                'url' => 'chapter/1/lesson/1',
                'title' => 'swaggerとは1',
                'remarks' => 'swaggerAPIの作成',
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'chapter_id' => 1,
                'url' =>'chapter/1/lesson/2',
                'title' => 'swaggerとは2',
                'remarks' => 'swaggerUIの見方',
                'status' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
