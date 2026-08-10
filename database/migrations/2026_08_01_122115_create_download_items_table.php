<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('download_id')->constrained('downloads')->cascadeOnDelete();
            $table->foreignId('episode_id')->constrained('episodes')->cascadeOnDelete();
            $table->enum('reason', ['missing', 'upgrade', 'refresh']);
            $table->unsignedInteger('torrent_file_index')->nullable();
            $table->string('torrent_file_name')->nullable();
            $table->string('imported_path')->nullable();
            $table->timestamps();

            $table->unique(['download_id', 'episode_id']);
            $table->index(['episode_id', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_items');
    }
};
