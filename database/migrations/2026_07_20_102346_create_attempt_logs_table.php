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
        Schema::create('attempt_logs', function (Blueprint $table) {
            $table->id();
            $table->text('text_attempts')->nullable();
            $table->text('details_attempts')->nullable();
            $table->integer('attempts_number')->nullable();
            $table->string('status');
            $table->string('event_type');
            $table->foreignId('notification_id')->constrained('notification_logs')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_logs');
    }
};
