<?php

use Illuminate\Database\Seeder;
use App\Model\ViewedOnceNotification;
use Carbon\CarbonImmutable;
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
                'notification_id' => 2,
                'student_id' => 2,
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
        ]);
    }
}
