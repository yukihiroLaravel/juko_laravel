<?php

namespace Database\Seeders;

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
                'name' => 'プログラミング基礎',
                'title' => 'PHP入門講座',
                'image' => 'course/4459908b-3cdf-4521-94fa-c2a9746d92e1.png',
                'status' => Course::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 2,
                'name' => 'プログラミング応用',
                'title' => 'Laravel入門講座',
                'image' => 'course/dbe1f6ef-66b4-4ce0-bfef-7555b6213bd4.png',
                'status' => Course::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 3,
                'name' => 'フロントエンド基礎',
                'title' => 'React入門講座',
                'image' => 'course/5c5edaa5-a1cf-42be-b56b-ff2692210df3.png',
                'status' => Course::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 4,
                'name' => '型付き言語入門',
                'title' => 'TypeScript入門講座',
                'image' => 'course/c258e47a-0f03-45c7-ae58-f6ab04484aa1.png',
                'status' => Course::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 1,
                'name' => 'データサイエンス入門',
                'title' => 'Python入門講座',
                'image' => 'course/c0fb049e-7419-4325-a992-3393dadaf21d.png',
                'status' => Course::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 2,
                'name' => 'フロントエンド応用',
                'title' => 'Vue入門講座',
                'image' => 'course/3904fc96-affc-4671-89a4-a2ae91dc27f8.png',
                'status' => Course::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 3,
                'name' => 'フロントエンド基礎',
                'title' => 'JavaScript入門講座',
                'image' => 'course/46eedaee-a724-4111-bada-592a5acd1eb5.png',
                'status' => Course::STATUS_PUBLIC,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
