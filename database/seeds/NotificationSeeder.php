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
                'content' => '4月1日〜10日の間、レッスン「変数とは？」がメンテナンスにつき閲覧できなくなります。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 2,
                'instructor_id' => 2,
                'title' => 'お知らせ機能が追加されました',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2024-04-01 00:00:00',
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
                'start_date' => '2024-04-01 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => 'TypeScript入門講座の内容が一部追加されました。確認をお願いします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 5,
                'instructor_id' => 1,
                'title' => 'トリイソース「変数とは？」閲覧について',
                'type' => Notification::TYPE_ALWAYS_INT,
                'start_date' => '2024-04-02 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '4月1日〜10日の間、レッスン「変数とは？」がメンテナンスにつき閲覧できなくなります。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 1,
                'instructor_id' => 1,
                'title' => '「近藤佑哉とは？」閲覧について',
                'type' => Notification::TYPE_ALWAYS_INT,
                'start_date' => '2024-04-10 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '4月10日〜20日の間、レッスン「変数とは？」がメンテナンスにつき閲覧できなくなります。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 1,
                'instructor_id' => 1,
                'title' => 'お知らせ機能が追加されました',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2024-04-21 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '受講生管理画面にお知らせ機能が追加されました。コースごとにお知らせをお送りします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 1,
                'instructor_id' => 1,
                'title' => 'Pytyon入門講座の更新について',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2024-04-22 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => 'Python入門講座の内容が一部追加されました。確認をお願いします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 5,
                'instructor_id' => 1,
                'title' => 'Javascript入門講座の更新について',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2024-04-22 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => 'Python入門講座の内容が一部追加されました。確認をお願いします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 5,
                'instructor_id' => 1,
                'title' => '「夏目あす花とは」閲覧について',
                'type' => Notification::TYPE_ALWAYS_INT,
                'start_date' => '2024-04-20 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '4月20日〜30日の間、レッスン「変数とは？」がメンテナンスにつき閲覧できなくなります。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 5,
                'instructor_id' => 1,
                'title' => 'すごいぞ機能が追加されました',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2024-04-15 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => '受講生管理画面にお知らせ機能が追加されました。コースごとにお知らせをお送りします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'course_id' => 1,
                'instructor_id' => 1,
                'title' => 'TypeScript入門講座の更新について',
                'type' => Notification::TYPE_ONCE_INT,
                'start_date' => '2024-04-30 00:00:00',
                'end_date' => CarbonImmutable::now()->addMonth(),
                'content' => 'PHP超入門講座の内容が一部追加されました。確認をお願いします。',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
        ]);
    }
}
