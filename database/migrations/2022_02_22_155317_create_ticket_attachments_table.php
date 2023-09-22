<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('path');
            $table->string('filename');
            $table->string('reply_id');
            $table->unsignedBigInteger('ticket_id');
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
        Schema::dropIfExists('ticket_attachments');
    }
}
