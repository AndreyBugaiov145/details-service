<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGrabberStatisticTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('grabber_statistic', function (Blueprint $table) {
            $table->id();
            $table->integer('parsing_setting_id');
            $table->string('parsing_status');
            $table->integer('request_count');
            $table->integer('request_time');
            $table->string('used_memory');
            $table->string('parsing_type');
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
        Schema::dropIfExists('grabber_statistic');
    }
}
