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
        Schema::table('partners', function (Blueprint $table) {
            $table->string('couverture')->nullable()->after('logo');
            $table->string('telephone', 20)->nullable()->after('couverture');
            $table->json('horaire')->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn(['couverture', 'telephone', 'horaire']);
        });
    }
};
