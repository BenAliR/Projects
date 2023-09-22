<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInviteAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invite_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('telephone')->nullable();
            $table->string('email')->unique();
            $table->longText('photo')->default('/uploads/default/1.jpg');
            $table->string('nom');
            $table->string('prenom');
            $table->string('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('cite')->nullable();
            $table->string('codepostal')->nullable();
            $table->string('user_type')->nullable();
            $table->string('etablissement')->nullable();
            $table->string('profession')->nullable();

            $table->tinyInteger('contact_email')->default(1);
            $table->tinyInteger('contact_phone')->default(1);
            $table->string('user_id')->nullable();
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
        Schema::dropIfExists('invite_attachments');
    }
}
