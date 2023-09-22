<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')
                ->nullable()
                ->default(null)
                ->references('id')
                ->on('tasks');
            $table->string('title')->nullable();;
            $table->longText('description')->nullable();;
            $table->string('priority')->nullable();;
            $table->string('slug');
            $table->timestamp('due_date')->nullable();;
            $table->string('status')
                ->comment('0=Pending, 1=Inprogress,2=Completed');
            $table->string('type');
            $table->json('tags')->nullable();
            $table->unsignedBigInteger('user_id');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('assign_id');

            $table->foreign('assign_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('project_id');

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
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
        Schema::dropIfExists('tasks');
    }
}
