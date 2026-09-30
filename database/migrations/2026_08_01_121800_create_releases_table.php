<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rss_feed_id')->constrained('rss_feeds')->cascadeOnDelete();
            $table->string('guid');
            $table->string('torrent_id')->nullable();
            $table->string('release_id')->nullable();
            $table->string('title');
            $table->string('torrent_url', 500);
            $table->enum('codec', ['hevc', 'avc']);
            $table->string('quality')->nullable();
            $table->unsignedSmallInteger('first_episode');
            $table->unsignedSmallInteger('last_episode');
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_current')->default(true)->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['rss_feed_id', 'guid']);
            $table->index(['rss_feed_id', 'is_current', 'codec', 'last_episode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releases');
    }
};
