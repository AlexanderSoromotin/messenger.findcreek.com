<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
                $table->bigInteger("user_id")->unsigned();
            $table->bigInteger("chat_group_id")->unsigned()->nullable();
            $table->string("name")->default("Новый чат");
            $table->string("type")->default("dialog");
            $table->string("avatar")->default("")->nullable();
            $table->timestamp("last_message_date")->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();

            $table->softDeletes();

            // Добавление внешнего ключа
            $table->foreign("user_id")->references("id")->on("users");
            $table->foreign("chat_group_id")->references("id")->on("chat_groups");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
