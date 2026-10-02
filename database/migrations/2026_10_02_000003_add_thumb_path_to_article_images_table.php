<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_images', function (Blueprint $table) {
            $table->string('thumb_path')->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('article_images', function (Blueprint $table) {
            $table->dropColumn('thumb_path');
        });
    }
};
