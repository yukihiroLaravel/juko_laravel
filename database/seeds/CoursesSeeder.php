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
                'title' => 'PHP入門講座',
                'image' => '/course/1/thumbnail.png',
                'status' => 'public',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 1,
                'title' => 'Laravel入門講座',
                'image' => '/course/2/thumbnail.png',
                'status' => 'public',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 1,
                'title' => 'React入門講座',
                'image' => '/course/3/thumbnail.png',
                'status' => 'public',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 1,
                'title' => 'TypeScript入門講座',
                'image' => '/course/4/thumbnail.png',
                'status' => 'public',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 1,
                'title' => 'Python入門講座',
                'image' => '/course/5/thumbnail.png',
                'status' => 'public',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 1,
                'title' => 'Vue入門講座',
                'image' => '/course/6/thumbnail.png',
                'status' => 'public',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 1,
                'title' => 'JavaScript入門講座',
                'image' => '/course/7/thumbnail.png',
                'status' => 'public',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
