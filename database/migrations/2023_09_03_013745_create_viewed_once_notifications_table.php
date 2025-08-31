<?php

use App\Model\Notification;
use App\Model\Student;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViewedOnceNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('viewed_once_notifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(Notification::class, 'notification_id')->constrained()->comment('お知らせID');
            $table->foreignIdFor(Student::class, 'student_id')->constrained()->comment('生徒ID');
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
        Schema::dropIfExists('viewed_once_notifications');
    }
}
