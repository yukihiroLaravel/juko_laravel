<?php

use App\Model\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Student::insert([
            [
                'nick_name' => '生徒ニックネーム1',
                'last_name' => '生徒',
                'first_name' => 'テスト1',
                'occupation' => 'システムエンジニア',
                'email' => 'test_student_1@example.com',
                'password' => Hash::make('password1'),
                'purpose' => '自己研鑽のため',
                'birthday' => Carbon::now(),
                'sex' => 1,
                'prefecture' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'nick_name' => '生徒ニックネーム2',
                'last_name' => '生徒',
                'first_name' => 'テスト2',
                'occupation' => 'フロントエンジニア',
                'email' => 'test_student_2@example.com',
                'password' => Hash::make('password2'),
                'purpose' => 'サーバーサイド知識を理解したい',
                'birthday' => Carbon::now(),
                'sex' => 2,
                'prefecture' => 2,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
