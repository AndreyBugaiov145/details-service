<?php

use App\Models\ParsingSetting;
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
            $table->string('car_models')->nullable();
            $table->integer('year');
            $table->timestamp('category_parsing_at')->nullable();
            $table->timestamp('detail_parsing_at')->nullable();
            $table->string('category_parsing_status')->default(ParsingSetting::STATUS_PENDING);
            $table->string('detail_parsing_status')->default(ParsingSetting::STATUS_PENDING);
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
