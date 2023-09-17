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
        $this->call(ChapterSeeder::class);
        $this->call(LessonSeeder::class);
        $this->call(LessonAttendanceSeeder::class);
        $this->call(NotificationSeeder::class);
        $this->call(StudentAuthorizationSeeder::class);
    }
}
