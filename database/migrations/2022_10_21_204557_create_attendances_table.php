<?php

use App\Model\Course;
use App\Model\Student;
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
            $table->foreignIdFor(Course::class, 'course_id')->constrained()->comment('講座ID');
            $table->foreignIdFor(Student::class, 'student_id')->constrained()->comment('生徒ID');
            $table->date('attendance_deadline')->nullable()->comment('受講期限日（計算済み）');
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
        Schema::dropIfExists('attendances');
    }
}
