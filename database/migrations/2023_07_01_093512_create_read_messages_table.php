<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('read_messages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_id")->unsigned();
            $table->bigInteger("chat_id")->unsigned();
            $table->bigInteger("message_id")->unsigned();
            $table->timestamps();

            // Добавление внешних ключей
            $table->foreign("user_id")->references("id")->on("users");
            $table->foreign("chat_id")->references("id")->on("chats");
            $table->foreign("message_id")->references("id")->on("messages");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('read_messages');
    }
};
