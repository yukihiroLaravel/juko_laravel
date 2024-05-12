<?php

use App\Model\Notification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

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
                'type' => Notification::TYPE_ALWAYS_INT,
                'start_date' => '2023-08-01 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '10月1日〜10日の間、レッスン「変数とは？」がメンテナンスにつき閲覧できなくなります。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 2,
                'instructor_id' => 2,
                'title' => 'お知らせ機能が追加されました',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2023-08-01 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '受講生管理画面にお知らせ機能が追加されました。コースごとにお知らせをお送りします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 4,
                'instructor_id' => 4,
                'title' => 'TypeScript入門講座の更新について',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2023-08-01 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => 'TypeScript入門講座の内容が一部追加されました。確認をお願いします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            // ここから下はコメントアウト
            [
                'course_id' => 1,
                'instructor_id' => 1,
                'title' => 'レッスン「変数とは？」閲覧について',
                'type' => Notification::TYPE_ALWAYS_INT,
                'start_date' => '2023-08-01 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '10月1日〜10日の間、レッスン「変数とは？」がメンテナンスにつき閲覧できなくなります。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 1,
                'instructor_id' => 1,
                'title' => 'レッスン「変数とは？」閲覧について',
                'type' => Notification::TYPE_ALWAYS_INT,
                'start_date' => '2023-08-01 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '10月1日〜10日の間、レッスン「変数とは？」がメンテナンスにつき閲覧できなくなります。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 1,
                'instructor_id' => 1,
                'title' => 'レッスン「変数とは？」閲覧について',
                'type' => Notification::TYPE_ALWAYS_INT,
                'start_date' => '2023-08-01 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '10月1日〜10日の間、レッスン「変数とは？」がメンテナンスにつき閲覧できなくなります。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
        ]);
    }
}
