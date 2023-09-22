<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDemandeInscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('demande_inscriptions', function (Blueprint $table) {
            $table->id();

            $table->string('telephone')->nullable();
            $table->string('email')->unique();
            $table->longText('photo')->default('/uploads/default/1.jpg');
            $table->string('country')->nullable();
            $table->string('id_code')->nullable();

            $table->string('typeecole')->nullable();
            $table->string('nom');
            $table->string('prenom');
            $table->string('adresse')->nullable();
            $table->string('adresse2')->nullable();
            $table->string('ville')->nullable();
            $table->string('cite')->nullable();
            $table->string('codepostal')->nullable();
            $table->string('user_type')->nullable();
            $table->longText('copie1')->nullable();
            $table->longText('copie2')->nullable();
            $table->longText('copie3')->nullable();
            $table->longText('copie4')->nullable();
            $table->string('demande_status')->default('en cours de traitement');
            $table->string('etablisement')->nullable();
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
        Schema::dropIfExists('demande_inscriptions');
    }
}
