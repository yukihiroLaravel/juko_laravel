<?php

namespace Database\Seeders;

use App\Model\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Tag::insert([
            [
                'instructor_id' => 1,
                'content' => 'バックエンド入門編',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 2,
                'content' => 'バックエンド講座',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 3,
                'content' => 'フロントエンドマスター講座',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 4,
                'content' => 'フロントエンド関連講座',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 2,
                'content' => 'フロントエンド講座',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 1,
                'content' => 'バックエンド応用編',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'instructor_id' => 1,
                'content' => '削除用のタグ',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
