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
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('bot_id');
            $table->string('name');
            $table->string('username');
            $table->string('type');
            $table->string('platform');
            $table->string('avatar_url')->nullable();
            $table->text('welcome_message')->nullable();
            $table->string('token')->nullable();
            $table->string('secret_token')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_ping_at')->nullable();
            $table->timestamps();

            $table->unique(['bot_id', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};
