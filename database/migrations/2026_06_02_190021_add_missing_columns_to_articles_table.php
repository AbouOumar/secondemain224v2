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
        Schema::table('articles', function (Blueprint $table) {
            if (!Schema::hasColumn('articles', 'statut')) {
                $table->string('statut')->default('en_vente')->after('is_published');
            }
            if (!Schema::hasColumn('articles', 'date_fin')) {
                $table->string('date_fin')->nullable()->after('statut');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['statut', 'date_fin']);
        });
    }
};
