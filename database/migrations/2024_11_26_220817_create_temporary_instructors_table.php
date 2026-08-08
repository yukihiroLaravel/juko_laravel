<?php

use App\Model\Instructor;
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
        Schema::create('temporary_instructors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(Instructor::class, 'manager_id')->nullable()->constrained()->comment('講師ID');
            $table->tinyInteger('trial_count')->unsigned()->comment('試行回数');
            $table->string('code', 4)->unique()->comment('認証コード');
            $table->string('token', 10)->unique()->comment('トークン');
            $table->dateTime('expire_at')->comment('認証コード有効期間');
            $table->string('nick_name', 50)->comment('ニックネーム');
            $table->string('last_name', 50)->comment('苗字');
            $table->string('first_name', 50)->comment('名前');
            $table->string('email', 255)->comment('メールアドレス');
            $table->string('type', 30)->comment('講師タイプ');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporary_instructors');
    }
};
