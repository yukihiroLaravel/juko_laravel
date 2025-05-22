<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('given_name_by_instructor', 50)->nullable()->comment('ユーザー名(仮)');
            $table->string('nick_name', 50)->nullable()->comment('ニックネーム');
            $table->string('last_name', 50)->nullable()->comment('苗字');
            $table->string('first_name', 50)->nullable()->comment('名前');
            $table->string('occupation', 50)->nullable()->comment('職業');
            $table->string('email', 255)->nullable()->unique()->comment('メールアドレス');
            $table->string('password', 255)->nullable()->comment('パスワード');
            $table->string('purpose', 50)->nullable()->comment('目的');
            $table->date('birth_date')->nullable()->comment('誕生日');
            $table->tinyInteger('gender')->nullable()->comment('性別');
            $table->string('address', 255)->nullable()->comment('都道府県');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->softDeletes();
            $table->dateTime('last_login_at')->nullable()->comment('最終ログイン日時');
            $table->dateTime('email_verified_at')->nullable()->comment('認証日時');
            $table->text('profile_image')->nullable()->comment('プロフィール画像');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('students');
    }
}
