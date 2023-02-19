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
            $table->string('slug')->unique();
            $table->string('s_number');
            $table->string('short_description')->nullable();
            $table->text('interchange_numbers')->nullable();
            $table->float('price');
            $table->integer('new_price')->nullable();
            $table->integer('shipping_price')->nullable();
            $table->integer('total_price')->nullable();
            $table->integer('coefficient')->nullable();
            $table->integer('stock')->default(0);
            $table->unsignedBigInteger('category_id')->unsigned();
            $table->unsignedBigInteger('currency_id')->unsigned();
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
