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
            $table->string('guid')->unique(); // hash из RSS
            $table->string('torrent_url');
            $table->string('torrent_id')->nullable(); // torrentId из AniLiberty
            $table->enum('codec', ['HEVC', 'AVC']);
            $table->json('episodes'); // массив [1, 2, 3] или диапазон [1, 12]
            $table->unsignedBigInteger('size')->default(0);
            $table->boolean('downloaded')->default(false);
            $table->timestamps();

            $table->index(['series_id', 'codec']);
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
