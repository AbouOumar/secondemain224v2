<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Programme « apporteur d'affaires » : un apporteur recrute des vendeurs avec son code
 * et touche une part de la commission de la plateforme sur la première vente de chacun.
 * Les taux de commission deviennent réglables par magasin partenaire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apporteurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('code', 12)->nullable()->unique(); // attribué à l'acceptation
            $table->string('status', 20)->default('en_attente')->index();
            $table->json('details')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('apporteur_id')->nullable()->constrained('apporteurs')->nullOnDelete();
            $table->timestamp('referred_at')->nullable();
        });

        Schema::create('apporteur_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apporteur_id')->constrained('apporteurs')->cascadeOnDelete();
            // Une seule commission par vendeur : celle de sa première vente.
            $table->foreignId('seller_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->unsignedInteger('commission_amount');
            $table->decimal('share', 5, 4);
            $table->unsignedInteger('amount');
            $table->string('status', 20); // verse | non_verse (apporteur suspendu)
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('commission_rate');
        });
        Schema::dropIfExists('apporteur_commissions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('apporteur_id');
            $table->dropColumn('referred_at');
        });
        Schema::dropIfExists('apporteurs');
    }
};
