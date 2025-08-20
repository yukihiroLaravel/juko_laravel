<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('instructor_id')->unsigned()->comment('講師ID');
            $table->string('title', 50)->comment('タイトル');
            $table->text('image')->comment('サムネイルファイルパス');
            $table->string('status', 30)->comment('ステータス');
            $table->dateTime('attendance_deadline')->nullable()->comment('受講期限（fixed用）');
            $table->string('expiration_mode')->default('none')->comment('受講期限タイプ: none, fixed, relative');
            $table->integer('valid_days')->nullable()->comment('受講開始から何日後まで有効か（relative用）');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->softDeletes();
            $table->foreign('instructor_id')->references('id')->on('instructors');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses');
    }
}
