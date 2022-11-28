<?php

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
<<<<<<< HEAD
                'title' => 'PHP入門講座',
=======
                'title' => 'PHP',
>>>>>>> feature/yuta/jka-65/lesson_api
                'image' => 'course/1/thumbnail.png',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
<<<<<<< HEAD
                'instructor_id' => 1,
                'title' => 'Laravel入門講座',
=======
                'instructor_id' => 2,
                'title' => 'Laravel',
>>>>>>> feature/yuta/jka-65/lesson_api
                'image' => 'course/2/thumbnail.png',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
