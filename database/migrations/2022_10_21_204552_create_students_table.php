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
            $table->string('nick_name', 50)->comment('ニックネーム');
            $table->string('last_name', 50)->comment('苗字');
            $table->string('first_name', 50)->comment('名前');
            $table->string('occupation', 50)->comment('職業');
            $table->string('email', 255)->unique()->comment('メールアドレス');
            $table->string('password', 255)->comment('パスワード');
            $table->string('purpose', 50)->comment('目的');
            $table->dateTime('birthday')->comment('誕生日');
            $table->tinyInteger('sex')->comment('性別');
            $table->tinyInteger('prefecture')->comment('都道府県');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->softDeletes();
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
