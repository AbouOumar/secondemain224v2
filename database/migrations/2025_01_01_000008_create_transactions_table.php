<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('montant', 12, 2);
            $table->string('reference', 20)->unique();
            $table->string('source');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->index('wallet_id');
            $table->index('type');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
