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
        Schema::create('torrents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rss_feed_id')->nullable()->constrained('rss_feeds')->nullOnDelete();
            $table->integer('season_number')->nullable();
            $table->string('torrent_url');
            $table->string('torrent_id')->nullable(); // torrentId из AniLiberty
            $table->enum('codec', ['hevc', 'avc']);
            $table->json('episodes'); // массив [1, 2, 3] или диапазон [1, 12]
            $table->unsignedTinyInteger('progress')->nullable();
            $table->unsignedMediumInteger('eta')->nullable();
            $table->boolean('downloaded')->default(false);
            $table->timestamps();

            $table->index(['series_id', 'season_number', 'codec']);
            $table->index(['series_id', 'downloaded']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('torrents');
    }
};
