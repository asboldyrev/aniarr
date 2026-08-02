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
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('series_id')->constrained('series');
            $table->integer('sonarr_id')->nullable();
            $table->foreignId('torrent_id')->nullable()->constrained('torrents')->nullOnDelete();
            $table->unsignedSmallInteger('season_number');
            $table->unsignedSmallInteger('episode_number');
            $table->enum('codec', ['avc', 'hevc']);
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();

            $table->index(['series_id', 'season_number', 'episode_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
