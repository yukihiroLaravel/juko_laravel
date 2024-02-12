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
        for ($i = 1; $i <= 30; $i++) {
            Student::create([
                'given_name_by_instructor' => 'ユーザー名(仮)' . $i,
                'nick_name' => '生徒ニックネーム' . $i,
                'last_name' => '生徒',
                'first_name' => 'テスト' . $i,
                'occupation' => 'システムエンジニア',
                'email' => 'test_student_' . $i . '@example.com',
                'password' => Hash::make('password' . $i),
                'purpose' => '自己研鑽のため',
                'birth_date' => Carbon::now(),
                'sex' => $i % 2 == 0 ? 2 : 1,
                'address' => $i % 2 == 0 ? '大阪府' : '東京都',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'last_login_at' => Carbon::now(),
                'email_verified_at' => Carbon::now(),
                'profile_image' => '/student/image' . $i . '.jpg',
            ]);
        }
    }
}
