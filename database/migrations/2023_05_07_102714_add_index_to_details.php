<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('details', function (Blueprint $table) {
            $table->index('s_number');
            $table->index('analogy_details');
            $table->string('interchange_numbers', 254)->change();
            $table->index('interchange_numbers');
            $table->boolean('is_fetched_i_n')->default(false);
            $table->string('info_link')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('details', function (Blueprint $table) {
            $table->dropIndex('s_number');
            $table->dropIndex('analogy_details');
            $table->dropIndex('interchange_numbers');
            $table->dropColumn('is_fetched_i_n');
            $table->dropColumn('info_link');
        });
    }
}
