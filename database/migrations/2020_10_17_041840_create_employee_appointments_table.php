<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_appointments', function (Blueprint $table) {
            $table->bigIncrements('id')->unsigned();
            $table->integer('is_active');
            $table->integer('duration_date');
            $table->time('start_date');
            $table->time('end_date');
            $table->date('active_day');
            $table->string('description');
            $table->integer('employee_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('product_id')->unsigned();
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_appointments');
    }
}