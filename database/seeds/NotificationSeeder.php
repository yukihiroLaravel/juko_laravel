<?php

use App\Model\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Notification::insert([
            [
                'course_id' => 1,
                'instructor_id' => 1,
                'title' => 'レッスン「変数とは？」閲覧について',
                'type' => 1,
                'start_date' => '2023-08-01 00:00:00',
                'end_date' => '2023-10-10 23:59:59',
                'content' => '10月1日〜10日の間、レッスン「変数とは？」がメンテナンスにつき閲覧できなくなります。',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'course_id' => 2,
                'instructor_id' => 2,
                'title' => 'お知らせ機能が追加されました',
                'type' => 2,
                'start_date' => '2023-08-01 00:00:00',
                'end_date' => '2023-10-10 23:59:59',
                'content' => '受講生管理画面にお知らせ機能が追加されました。コースごとにお知らせをお送りします。',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
