<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_deadlines', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('期限設定ID');
            $table->bigInteger('course_id')->unsigned()->comment('講座ID');
            $table->date('fixed_date')->nullable()->comment('固定期限日（fixed_dateタイプの場合のみ使用）');
            $table->integer('relative_days')->nullable()->comment('相対日数（relative_daysタイプの場合のみ使用）');
            $table->timestamp('created_at');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_deadlines');
    }
};
