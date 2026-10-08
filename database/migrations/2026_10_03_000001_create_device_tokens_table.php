<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jetons Firebase Cloud Messaging des téléphones (un par installation de l'app).
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 512)->unique();
            $table->string('platform', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
