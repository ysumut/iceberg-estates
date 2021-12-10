<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppointmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('address_destination');
            $table->double('distance'); // metres
            $table->dateTime('office_leaving_date');
            $table->dateTime('appointment_date');
            $table->dateTime('office_arrival_date');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->foreignId('contact_id')->references('id')->on('contacts');
            $table->timestamps();
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
        Schema::dropIfExists('appointments');
    }
}
