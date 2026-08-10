<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->foreignId('release_id')->constrained('releases')->restrictOnDelete();
            $table->enum('trigger', ['automatic', 'manual']);
            $table->enum('status', [
                'pending',
                'preparing',
                'downloading',
                'importing',
                'completed',
                'cancelled',
                'failed',
            ])->default('pending')->index();
            $table->string('qbit_hash')->nullable()->index();
            $table->string('qbit_tag')->nullable();
            $table->unsignedTinyInteger('progress')->nullable();
            $table->unsignedMediumInteger('eta_seconds')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['season_id', 'status']);
            $table->index(['release_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downloads');
    }
};
