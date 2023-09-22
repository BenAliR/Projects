<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppInfoConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_info_configurations', function (Blueprint $table) {
            $table->id();
            $table->longText('app_name')->nullable();
            $table->longText('app_url')->nullable();
            $table->longText('app_logo')->nullable();
            $table->longText('url_facebook')->nullable();
            $table->longText('url_linkedin')->nullable();
            $table->longText('url_instagram')->nullable();
            $table->longText('url_website')->nullable();
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
        Schema::dropIfExists('app_info_configurations');
    }
}
