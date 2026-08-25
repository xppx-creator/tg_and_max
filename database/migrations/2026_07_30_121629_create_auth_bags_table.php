<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up()
    {
        Schema::create('auth_bags', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('integration_code');
            $table->string('hash');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('auth_bags');
    }
};
