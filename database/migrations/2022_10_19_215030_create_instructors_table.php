<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstructorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instructors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nick_name', 50)->comment('ニックネーム');
            $table->string('last_name', 50)->comment('苗字');
            $table->string('first_name', 50)->comment('名前');
            $table->string('email', 255)->comment('メールアドレス');
            $table->string('password', 255)->comment('パスワード');
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
        Schema::dropIfExists('instructors');
    }
}
