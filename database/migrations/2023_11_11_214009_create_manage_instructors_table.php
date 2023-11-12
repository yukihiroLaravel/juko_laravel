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
            $table->bigInteger('instructor_id')->unsigned()->comment('通常講師');
            $table->bigInteger('managerid')->unsigned()->comment('マネージャ');
            $table->datetime('created_at');
            $table->datetime('updated_at');
            $table->softDeletes();
            $table->foreign('instructor_id')->references('id')->on('instructors');
            $table->foreign('managerid')->references('id')->on('instructors');
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
