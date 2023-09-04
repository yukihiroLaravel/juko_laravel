<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_students', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('notification_id')->unsigned();
            $table->bigInteger('student_id')->unsigned();
            $table->boolean('has_viewed')->default(false);
            $table->dateTime('created_at');
            $table->dateTime('updated_at');    
            $table->foreign('notification_id')->references('id')->on('notifications')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_students');
    }
}
