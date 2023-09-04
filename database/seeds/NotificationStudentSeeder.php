<?php

use Illuminate\Database\Seeder;
use Carbon\Carbon;

class NotificationStudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('notification_students')->insert([
            'notification_id' => 1,
            'student_id' => 1,
            'has_viewed' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        DB::table('notification_students')->insert([
            'notification_id' => 2,
            'student_id' => 1,
            'has_viewed' => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
