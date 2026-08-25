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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts');
            $table->unsignedBigInteger('lead_id');
            $table->string('platform');
            $table->foreignId('bot_id')->nullable()->constrained('bots')->nullOnDelete();
            $table->string('bot_label')->nullable();
            $table->unsignedBigInteger('chat_id')->nullable();
            $table->string('chat_label')->nullable();
            $table->foreignUuid('trigger_id')->nullable()->constrained('triggers')->nullOnDelete();
            $table->string('trigger_type');
            $table->string('trigger_name');
            $table->text('message')->nullable();
            $table->string('format_message')->nullable();
            $table->text('error_message')->nullable();
            $table->json('message_ids')->nullable();
            $table->string('status');
            $table->integer('field_id')->nullable();
            $table->string('source_type');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
