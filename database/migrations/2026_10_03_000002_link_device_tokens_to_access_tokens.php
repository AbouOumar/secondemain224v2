<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Un téléphone ne reçoit plus de notifications dès que la session qui l'a
        // enregistré est fermée ou révoquée (déconnexion, « tous les appareils »…).
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->foreignId('personal_access_token_id')->nullable()->after('user_id')
                ->constrained('personal_access_tokens')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('personal_access_token_id');
        });
    }
};
