<?php

use App\Model\Course;
use App\Model\Instructor;
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
            $table->foreignIdFor(Course::class, 'course_id')->constrained()->comment('講座ID');
            $table->foreignIdFor(Instructor::class, 'instructor_id')->constrained()->comment('講師ID');
            $table->string('title', 50)->comment('タイトル');
            $table->enum('type', ['always', 'once'])->comment('表示パターン区分');
            $table->dateTime('start_date')->comment('開始日時');
            $table->dateTime('end_date')->comment('終了日時');
            $table->enum('status', ['public', 'private'])->default('private')->comment('公開状態');
            $table->text('content')->comment('本文');
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
        Schema::dropIfExists('notifications');
    }
}
