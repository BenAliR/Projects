<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEvaluationCriteriaScoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluation_criteria_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_criteria_id');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            $table->integer('score');
            $table->timestamps();

            $table->foreign('evaluation_criteria_id')->references('id')->on('evaluation_criterias')->onDelete('cascade');
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evaluation_criteria_scores');
    }
}
