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
            $table->integer('sonarr_id')->nullable();
            $table->unsignedBigInteger('thetvdb_id')->index();
            $table->string('thetvdb_slug');
            $table->string('poster_url')->nullable();
            $table->string('poster_path')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->enum('status', [
                'waiting',
                'new_episodes',
                'downloading',
                'processing_sonarr',
                'syncing_jellyfin',
                'done',
                'error',
            ])->default('waiting')->index();
            $table->timestamp('last_updated')->nullable();
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
