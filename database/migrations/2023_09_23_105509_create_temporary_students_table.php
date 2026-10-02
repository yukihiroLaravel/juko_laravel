<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temporary_students', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->tinyInteger('trial_count')->unsigned()->comment('試行回数');
            $table->string('code', 4)->unique()->comment('認証コード');
            $table->string('token', 10)->unique()->comment('トークン');
            $table->dateTime('expire_at')->comment('認証コード有効期間');
            $table->string('nick_name', 50)->comment('ニックネーム');
            $table->string('last_name', 50)->comment('苗字');
            $table->string('first_name', 50)->comment('名前');
            $table->string('email', 255)->comment('メールアドレス');
            $table->string('occupation', 50)->nullable()->comment('職業');
            $table->string('purpose', 50)->nullable()->comment('目的');
            $table->date('birth_date')->nullable()->comment('誕生日');
            $table->string('gender', 10)->nullable()->comment('性別');
            $table->string('address', 255)->nullable()->comment('都道府県');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('temporary_students');
    }
};
