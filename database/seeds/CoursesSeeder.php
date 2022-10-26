<?php

namespace  App\Database\Seeds;

use App\Model\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Course::insert([
            [
                'instructor_id' => 1,
                'title' => 'PHP入門講座',
                'image' => 'course/1/thumbnail.png',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 1,
                'title' => 'Laravel入門講座',
                'image' => 'course/2/thumbnail.png',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
