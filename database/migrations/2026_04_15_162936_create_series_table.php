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
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('thetvdb_id')->index();
            $table->string('thetvdb_slug');
            $table->string('rss_url');
            $table->string('poster_url')->nullable();
            $table->string('poster_path')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->enum('status', [
                'waiting',
                'new_episodes',
                'downloading_avc',
                'processing_sonarr',
                'downloading_hevc',
                'syncing_jellyfin',
                'done',
                'error'
            ])->default('waiting')->index();
            $table->unsignedTinyInteger('progress')->nullable();
            $table->timestamp('eta')->nullable();
            $table->string('active_torrent_hash')->nullable();
            $table->string('active_download_path')->nullable();
            $table->boolean('active_download_is_hevc')->default(false);
            $table->enum('codec', ['AVC', 'HEVC'])->default('AVC');
            $table->string('last_episodes')->nullable();
            $table->text('error_message')->nullable();
            $table->boolean('sonarr_connected')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
