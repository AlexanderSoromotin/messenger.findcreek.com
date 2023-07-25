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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
                $table->bigInteger("reply_to_message_id")->unsigned()->nullable();
            $table->bigInteger("chat_id")->unsigned();
            $table->bigInteger("sender_id")->unsigned()->nullable();
            $table->boolean('is_silent')->default(false);
            $table->boolean('is_technical')->default(false);
            $table->text('technical_data', 10000)->nullable();

            $table->timestamps();

            $table->softDeletes();

            // Добавление внешнего ключа
            $table->foreign("chat_id")->references("id")->on("chats");
            $table->foreign("sender_id")->references("id")->on("users");

            // Добавление внешнего ключа для поля "reply_to_message_id"
            $table->foreign("reply_to_message_id")->references("id")->on("messages");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
