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
                'given_name_by_instructor' => 'ユーザー名(仮)1',
                'nick_name' => '生徒ニックネーム1',
                'last_name' => '生徒',
                'first_name' => 'テスト1',
                'occupation' => 'システムエンジニア',
                'email' => 'test_student_1@example.com',
                'password' => Hash::make('password1'),
                'purpose' => '自己研鑽のため',
                'birth_date' => Carbon::today(),
                'sex' => 1,
                'address' => '東京都',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'last_login_at' => Carbon::now(),
                'email_verified_at' => Carbon::now(),
                'profile_image' => '/student/image1.jpg',
            ],
            [
                'given_name_by_instructor' => 'ユーザー名(仮)2',
                'nick_name' => '生徒ニックネーム2',
                'last_name' => '生徒',
                'first_name' => 'テスト2',
                'occupation' => 'フロントエンジニア',
                'email' => 'test_student_2@example.com',
                'password' => Hash::make('password2'),
                'purpose' => 'サーバーサイド知識を理解したい',
                'birth_date' => Carbon::now(),
                'sex' => 2,
                'address' => '大阪府',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'last_login_at' => Carbon::now(),
                'email_verified_at' => Carbon::now(),
                'profile_image' => '/student/image2.jpg',
            ],
            [
                'given_name_by_instructor' => 'ユーザー名(仮)3',
                'nick_name' => '生徒ニックネーム3',
                'last_name' => '生徒',
                'first_name' => 'テスト3',
                'occupation' => 'バックエンドエンジニア',
                'email' => 'test_student_3@example.com',
                'password' => Hash::make('password3'),
                'purpose' => 'フロントエンドの知識を理解したい',
                'birth_date' => Carbon::now(),
                'sex' => 2,
                'address' => '北海道',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'last_login_at' => Carbon::now(),
                'email_verified_at' => Carbon::now(),
                'profile_image' => '/student/image3.jpg',
            ],
        ]);
    }
}
