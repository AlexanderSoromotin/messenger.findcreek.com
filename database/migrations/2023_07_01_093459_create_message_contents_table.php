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
        Schema::create('message_contents', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("message_id")->unsigned();
            $table->string("text", 15000)->default("");
            $table->string("reply_text", 500)->default("");
            $table->text("attachments_ids")->nullable();
            $table->timestamps();

            $table->foreign("message_id")->references("id")->on("messages");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_contents');
    }
};
