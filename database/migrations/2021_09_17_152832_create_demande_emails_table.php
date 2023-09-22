<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDemandeEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('demande_emails', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->string('sujet');
            $table->longText('emailcontent');
            $table->unsignedBigInteger('demande_id');
            $table->foreign('demande_id')->references('id')->on('demande_inscriptions')->onDelete('cascade');
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
        Schema::dropIfExists('demande_emails');
    }
}
