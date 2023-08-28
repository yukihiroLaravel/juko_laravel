<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('course_id')->unsigned();
            $table->bigInteger('instructor_id')->unsigned();
            $table->string('title', 50)->comment('タイトル');
            $table->tinyInteger('type')->comment('表示パターン区分'); //1は"always"（期間中ずっと表示）2は"once"（1度のみ表示）として定数にして扱う
            $table->dateTime('start_date')->comment('開始日時');
            $table->dateTime('end_date')->comment('終了日時');
            $table->text('content')->comment('本文');
            $table->timestamps();
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
        Schema::dropIfExists('notifications');
    }
}
