<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(InstructorSeeder::class);
        $this->call(StudentSeeder::class);
        $this->call(CoursesSeeder::class);
        $this->call(AttendanceSeeder::class);
<<<<<<< HEAD
=======
        $this->call(LessonsSeeder::class);
        $this->call(LessonAttendancesSeeder::class);
        $this->call(ChapterSeeder::class);
>>>>>>> feature/yuta/jka-65/lesson_api
    }
}
