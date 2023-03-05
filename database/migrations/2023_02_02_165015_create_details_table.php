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
            $table->float('price');
            $table->float('new_price')->nullable();
            $table->float('shipping_price')->nullable();
            $table->float('total_price')->nullable();
            $table->float('coefficient')->nullable();
            $table->integer('stock')->default(0);
            $table->integer('partkey')->nullable();
            $table->unsignedBigInteger('category_id')->unsigned();
            $table->unsignedBigInteger('currency_id')->unsigned();
            $table->unique('title','category_id');
            $table->boolean('is_parsing_analogy_details')->default(false);
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
