<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('montant', 12, 2);
            $table->string('currency', 10)->default('GNF');
            $table->string('methode');
            $table->string('status');
            $table->string('external_ref')->nullable();
            $table->json('external_data')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->index('user_id');
            $table->index('external_ref');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
