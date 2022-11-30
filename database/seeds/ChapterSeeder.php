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
                'title' => 'Laravel入門1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 1,
                'title' => '基本コードを覚えよう',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
