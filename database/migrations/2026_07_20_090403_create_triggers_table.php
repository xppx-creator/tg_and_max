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
        Schema::create('triggers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('bot_id')->nullable()->constrained('bots')->nullOnDelete();
            $table->string('label');
            $table->string('source_chat');
            $table->foreignId('chat_id')->nullable()->constrained('chats')->nullOnDelete();
            $table->unsignedBigInteger('chat_field_id')->nullable();
            $table->unsignedBigInteger('field_id')->nullable();
            $table->text('message')->nullable();
            $table->json('buttons')->nullable();
            $table->string('format_message')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('triggers');
    }
};
