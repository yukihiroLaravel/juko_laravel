<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLessonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('chapter_id')->unsigned()->comment('チャプターID');
            $table->text('url')->nullable()->comment('URL');
            $table->string('title', 50)->comment('タイトル');
            $table->text('remarks')->nullable()->comment('備考');
            $table->string('status', 30)->comment('レッスン受講状態');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->softDeletes();
            $table->unsignedTinyInteger('order')->coment('順番');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lessons');
    }
}
