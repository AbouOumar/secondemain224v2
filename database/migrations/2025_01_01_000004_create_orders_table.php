<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->decimal('prix_article', 12, 2);
            $table->boolean('with_delivery')->default(false);
            $table->decimal('delivery_prix', 10, 2)->nullable();
            $table->decimal('total', 12, 2);
            $table->string('status');
            $table->text('annule_raison')->nullable();
            $table->timestamps();
            $table->index('buyer_id');
            $table->index('seller_id');
            $table->index('status');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
