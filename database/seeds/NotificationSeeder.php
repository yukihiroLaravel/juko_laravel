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
                'start_date' => '2024-04-01 00:00:00',
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
            [
                'course_id' => 1,
                'instructor_id' => 2,
                'title' => 'お知らせ機能が追加されました',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2024-04-15 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '受講生管理画面にお知らせ機能が追加されました。コースごとにお知らせをお送りします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 5,
                'instructor_id' => 1,
                'title' => 'ドッスン「変数とは？」閲覧について',
                'type' => Notification::TYPE_ALWAYS_INT,
                'start_date' => '2024-04-02 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '10月1日〜10日の間、レッスン「変数とは？」がメンテナンスにつき閲覧できなくなります。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 5,
                'instructor_id' => 1,
                'title' => 'ありがとう機能が追加されました',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2024-04-03 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '受講生管理画面にお知らせ機能が追加されました。コースごとにお知らせをお送りします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 5,
                'instructor_id' => 1,
                'title' => 'お願い入門講座の更新について',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2024-04-05 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => 'TypeScript入門講座の内容が一部追加されました。確認をお願いします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 5,
                'instructor_id' => 1,
                'title' => '最強「変数とは？」閲覧について',
                'type' => Notification::TYPE_ALWAYS_INT,
                'start_date' => '2024-04-07 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '10月1日〜10日の間、レッスン「変数とは？」がメンテナンスにつき閲覧できなくなります。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 5,
                'instructor_id' => 1,
                'title' => '時間調整機能が追加されました',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2024-04-10 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '受講生管理画面にお知らせ機能が追加されました。コースごとにお知らせをお送りします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 1,
                'instructor_id' => 1,
                'title' => 'PHP入門講座の更新について',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2024-04-11 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => 'PHP入門講座の内容が一部追加されました。確認をお願いします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 5,
                'instructor_id' => 1,
                'title' => 'Python「変数とは？」閲覧について',
                'type' => Notification::TYPE_ALWAYS_INT,
                'start_date' => '2024-04-21 00:00:00',
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
        ]);
    }
}
