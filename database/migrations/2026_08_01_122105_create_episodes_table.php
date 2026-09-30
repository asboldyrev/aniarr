<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->integer('sonarr_id')->nullable()->unique();
            $table->integer('sonarr_file_id')->nullable();
            $table->unsignedSmallInteger('episode_number');
            $table->string('title');
            $table->boolean('has_file')->default(false)->index();
            $table->enum('file_codec', ['avc', 'hevc'])->nullable()->index();
            $table->timestamp('file_date_added')->nullable();
            $table->timestamps();

            $table->unique(['season_id', 'episode_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episodes');
    }
};
