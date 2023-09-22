<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhmcsConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('whmcs_configurations', function (Blueprint $table) {
            $table->id();
            $table->longText('wh_username_client')->nullable();
            $table->longText('wh_password_client')->nullable();
            $table->longText('wh_accesskey')->nullable();
            $table->longText('wh_url')->nullable();
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
        Schema::dropIfExists('whmcs_configurations');
    }
}
