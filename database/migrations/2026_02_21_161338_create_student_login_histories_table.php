<?php

use App\Model\Student;
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
        Schema::create('student_login_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(Student::class)->constrained();
            $table->dateTime('logged_in_at')->comment('ログイン日時');
            $table->index('logged_in_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_login_histories');
    }
};
