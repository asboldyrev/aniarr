<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rss_feeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->unique()->constrained('seasons')->cascadeOnDelete();
            $table->string('rss_url', 500);
            $table->boolean('enabled')->default(true)->index();
            $table->string('last_rss_hash', 64)->nullable();
            $table->timestamp('last_rss_check')->nullable();
            $table->timestamp('last_rss_success_at')->nullable();
            $table->timestamp('last_error_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rss_feeds');
    }
};
