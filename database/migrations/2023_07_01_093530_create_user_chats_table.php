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
        Schema::create('user_chats', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("user_id")->unsigned();
            $table->bigInteger("chat_id")->unsigned();
            $table->string("is_active")->default("true");
            $table->timestamps();

            // Добавление внешних ключей
            $table->foreign("user_id")->references("id")->on("users")->onDelete('cascade');
            $table->foreign("chat_id")->references("id")->on("chats")->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_chats');
    }
};
