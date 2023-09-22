<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('description');
            $table->longText('project_avatar')->nullable();
            $table->string('project_status')->default('En attente');
            $table->string('type')->nullable();
            $table->longText('dev_technologie')->nullable();
            $table->timestamp('duedate')->nullable();
            $table->string('domaine')->nullable();
            $table->string('slug')->nullable();
            $table->string('team_id')->nullable();
            $table->string('team_size')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->integer('serviceid')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->softDeletes();
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
        Schema::dropIfExists('projets');
    }
}
