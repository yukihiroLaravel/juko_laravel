<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('course_id')->unsigned()->comment('講座ID');
            $table->bigInteger('student_id')->unsigned()->comment('生徒ID');
            $table->dateTime('started_at')->nullable()->comment('受講開始日時（relative用）');
            $table->dateTime('expired_at')->nullable()->comment('受講期限（relative用、有効期限）');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->softDeletes();
            $table->foreign('course_id')->references('id')->on('courses');
            $table->foreign('student_id')->references('id')->on('students');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
}
