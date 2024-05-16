<?php

use App\Model\ViewedOnceNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class ViewedOnceNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ViewedOnceNotification::insert([
            [
                'notification_id' => 1,
                'student_id' => 1,
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'notification_id' => 1,
                'student_id' => 2,
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'notification_id' => 2,
                'student_id' => 3,
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'notification_id' => 3,
                'student_id' => 4,
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
        ]);
    }
}
