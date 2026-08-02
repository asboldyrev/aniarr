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
        Schema::create('rss_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained('series')->cascadeOnDelete();
            $table->integer('season_number')->nullable();
            $table->string('rss_url', 500);
            $table->timestamp('last_rss_check')->nullable();
            $table->timestamps();

            $table->index('series_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rss_feeds');
    }
};
