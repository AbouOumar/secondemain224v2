<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('titre');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('prix', 12, 2);
            $table->string('currency', 10)->default('GNF');
            $table->unsignedInteger('stock')->default(1)->nullable();
            $table->string('etat')->default('bon');
            $table->year('annee')->nullable();
            $table->string('localisation');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('with_delivery')->default(true);
            $table->decimal('delivery_prix', 10, 2)->nullable();
            $table->boolean('is_boosted')->default(false);
            $table->timestamp('boosted_until')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('vue_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
            $table->index('is_published');
            $table->index('is_boosted');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
