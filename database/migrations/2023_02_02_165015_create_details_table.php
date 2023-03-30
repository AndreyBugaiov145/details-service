<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('details', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('s_number');
            $table->text('short_description')->nullable();
            $table->text('interchange_numbers')->nullable();
            $table->float('price')->default(0);
            $table->float('us_shipping_price')->default(1);
            $table->float('ua_shipping_price')->default(0);
            $table->float('price_markup')->default(0);
            $table->integer('stock')->default(0);
            $table->integer('partkey')->nullable();
            $table->text('jsn')->nullable();
            $table->unsignedBigInteger('category_id')->unsigned();
            $table->unsignedBigInteger('currency_id')->unsigned();
            $table->unique(['category_id', 'partkey']);
            $table->boolean('is_parsing_analogy_details')->default(false);
            $table->text('analogy_details')->nullable();
            $table->boolean('is_manual_added')->default(false);
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
        Schema::dropIfExists('details');
    }
}
