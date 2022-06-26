<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuthorityAgentChatMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authority_agent_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('authority_agent_chat_room_id')->references('id')->on('authority_agent_chat_rooms');
            $table->foreignId('user_id')->references('id')->on('users');
            $table->mediumText('message');
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
        Schema::dropIfExists('authority_agent_chat_messages');
    }
}
