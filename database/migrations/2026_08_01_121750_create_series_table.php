<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('sonarr_id')->nullable()->unique();
            $table->unsignedBigInteger('thetvdb_id')->unique();
            $table->string('thetvdb_slug');
            $table->string('poster_url')->nullable();
            $table->string('poster_path')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->boolean('monitored')->default(true)->index();
            $table->timestamp('last_sonarr_sync_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
