<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement(
            'DELETE duplicate FROM viewed_once_notifications AS duplicate
            INNER JOIN viewed_once_notifications AS original
                ON duplicate.notification_id = original.notification_id
                AND duplicate.student_id = original.student_id
                AND duplicate.id > original.id'
        );

        Schema::table('viewed_once_notifications', function (Blueprint $table) {
            $table->unique(['notification_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('viewed_once_notifications', function (Blueprint $table) {
            // 外部キー用のインデックスを確保してからUNIQUE制約を削除する
            $table->index('notification_id');
            $table->dropUnique(['notification_id', 'student_id']);
        });
    }
};
