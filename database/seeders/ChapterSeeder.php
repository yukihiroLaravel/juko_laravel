<?php

namespace Database\Seeders;

use App\Enums\Chapter\StatusEnum as ChapterStatusEnum;
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
                'status' => ChapterStatusEnum::PUBLIC->value,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 1,
                'order' => 2,
                'title' => 'PHPの基礎を学ぼう',
                'status' => ChapterStatusEnum::PUBLIC->value,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 1,
                'order' => 3,
                'title' => 'PHPの応用にチャレンジしよう',
                'status' => ChapterStatusEnum::PUBLIC->value,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 2,
                'order' => 1,
                'title' => 'Laravelとは？',
                'status' => ChapterStatusEnum::PUBLIC->value,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 2,
                'order' => 2,
                'title' => 'Laravelの基礎を学ぼう',
                'status' => ChapterStatusEnum::PUBLIC->value,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 5,
                'order' => 1,
                'title' => 'Pythonとは？',
                'status' => ChapterStatusEnum::PUBLIC->value,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
