<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class  CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');  // each user can receive notification related to project or anything else
            $table->string('type');  // Task Created , Project Status Changed
            $table->string('title');
            $table->string('message');
            $table->boolean('read')->default(false); // Add a column to track if the notification has been read
            $table->timestamp('read_at')->nullable();
            $table->morphs('notifiable');  // Add a column to track if the notification has been read 'App\Models\Team'
           $table->text('data');
            $table->timestamps();
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
        Schema::dropIfExists('notifications');
    }
}
