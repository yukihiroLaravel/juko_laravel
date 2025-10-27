<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManageInstructorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manage_instructors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignIdFor(App\Model\Instructor::class, 'instructor_id')->constrained()->comment('通常講師ID');
            $table->foreignIdFor(App\Model\Instructor::class, 'manager_id')->constrained()->comment('マネージャID');
            $table->datetime('created_at');
            $table->datetime('updated_at');
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
        Schema::dropIfExists('manage_instructors');
    }
}
