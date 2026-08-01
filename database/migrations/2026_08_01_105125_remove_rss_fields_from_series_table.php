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
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn(['rss_url', 'last_rss_hash', 'last_rss_check']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->string('rss_url');
            $table->string('last_rss_hash')->nullable();
            $table->timestamp('last_rss_check')->nullable();
        });
    }
};
