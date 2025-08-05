<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CourseTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('course_tag')->insert([
            [
                'course_id' => 1,
                'tag_id' => 1,
                'created_at' => Carbon::now(),
            ],
            [
                'course_id' => 2,
                'tag_id' => 2,
                'created_at' => Carbon::now(),
            ],
            [
                'course_id' => 3,
                'tag_id' => 3,
                'created_at' => Carbon::now(),
            ],
            [
                'course_id' => 4,
                'tag_id' => 4,
                'created_at' => Carbon::now(),
            ],
            [
                'course_id' => 5,
                'tag_id' => 6,
                'created_at' => Carbon::now(),
            ],
            [
                'course_id' => 7,
                'tag_id' => 3,
                'created_at' => Carbon::now(),
            ],
        ]);
    }
}
