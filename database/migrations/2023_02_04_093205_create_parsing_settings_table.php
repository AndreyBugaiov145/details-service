<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParsingSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parsing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('car_models');
            $table->integer('year');
            $table->time('category_parsing_at')->nullable();
            $table->time('detail_parsing_at')->nullable();
            $table->string('category_parsing_status')->nullable();
            $table->string('detail_parsing_status')->nullable();
            $table->boolean('is_parsing_analogy_details')->default(false);
            $table->boolean('is_show')->default(true);
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
        Schema::dropIfExists('parsing_settings');
    }
}
