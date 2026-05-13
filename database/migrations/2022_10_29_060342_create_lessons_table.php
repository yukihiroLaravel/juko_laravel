<?php

use App\Model\Chapter;
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
            $table->foreignIdFor(Chapter::class, 'chapter_id')->constrained()->comment('チャプターID');
            $table->text('url')->nullable()->comment('URL');
            $table->string('title', 50)->comment('タイトル');
            $table->text('remarks')->nullable()->comment('備考');
            $table->string('status', 30)->comment('ステータス(draft / public / private)');
            $table->dateTime('created_at');
            $table->dateTime('updated_at');
            $table->unsignedTinyInteger('order')->comment('順番');
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
        Schema::dropIfExists('lessons');
    }
}
