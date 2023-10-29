<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentAuthorization extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('student_authorization', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('student_id')->unsigned()->unique()->comment('仮登録生徒識別ID');
            $table->tinyInteger('trial_count')->unsigned()->comment('試行回数');
            $table->string('code', 4)->unique()->comment('認証コード');
            $table->string('token', 10)->unique()->comment('トークン');
            $table->dateTime('expire_at')->comment('認証コード有効期間');
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
        Schema::dropIfExists('student_authorization');
    }
}
