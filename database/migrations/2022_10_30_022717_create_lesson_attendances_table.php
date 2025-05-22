<?php

use App\Model\Attendance;
use App\Model\Lesson;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLessonAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lesson_attendances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(Lesson::class)->constrained()->comment('レッスンID');
            $table->foreignIdFor(Attendance::class)->constrained()->comment('受講ID');
            $table->string('status', 30)->comment('レッスン受講状態');
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
        Schema::dropIfExists('lesson_attendances');
    }
}
