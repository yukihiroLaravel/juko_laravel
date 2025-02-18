<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Model\Course;
use App\Model\Tag;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_tag', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(Course::class)->constrained();
            $table->foreignIdFor(Tag::class)->constrained();
            $table->dateTime('created_at')->nullable();
            $table->unique(['course_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_tag');
    }
};
