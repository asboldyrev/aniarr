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
            $table->timestamp('last_rss_check')->nullable();
            $table->string('last_rss_hash')->nullable(); // последний известный guid
            $table->boolean('has_hevc')->default(false);
            $table->boolean('has_avc')->default(false);
            $table->boolean('upgrade_to_hevc')->default(false); // флаг для перекачивания
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn([
                'last_rss_check',
                'last_rss_hash',
                'has_hevc',
                'has_avc',
                'upgrade_to_hevc',
            ]);
        });
    }
};
