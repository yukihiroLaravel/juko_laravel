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
            $table->string('deadline_type', 50)->default('none')->comment('期限タイプ（none, fixed_date, relative_days）');
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
