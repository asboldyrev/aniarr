<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->nullable()->constrained('series')->nullOnDelete();
            $table->foreignId('season_id')->nullable()->constrained('seasons')->nullOnDelete();
            $table->foreignId('download_id')->nullable()->constrained('downloads')->nullOnDelete();
            $table->string('source')->nullable()->index();
            $table->string('event')->nullable()->index();
            $table->enum('type', ['debug', 'info', 'warning', 'error'])->default('info')->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['series_id', 'created_at']);
            $table->index(['season_id', 'created_at']);
            $table->index(['download_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
