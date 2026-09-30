<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = collect(Schema::getIndexes('downloads'))
            ->keyBy('name');

        if ($indexes->has('downloads_qbit_hash_unique')) {
            Schema::table('downloads', function (Blueprint $table) {
                $table->dropUnique('downloads_qbit_hash_unique');
            });
        }

        $indexes = collect(Schema::getIndexes('downloads'))
            ->keyBy('name');

        if (! $indexes->has('downloads_qbit_hash_index')) {
            Schema::table('downloads', function (Blueprint $table) {
                $table->index('qbit_hash');
            });
        }
    }

    public function down(): void
    {
        $indexes = collect(Schema::getIndexes('downloads'))
            ->keyBy('name');

        if ($indexes->has('downloads_qbit_hash_index')) {
            Schema::table('downloads', function (Blueprint $table) {
                $table->dropIndex('downloads_qbit_hash_index');
            });
        }

        $indexes = collect(Schema::getIndexes('downloads'))
            ->keyBy('name');

        if (! $indexes->has('downloads_qbit_hash_unique')) {
            Schema::table('downloads', function (Blueprint $table) {
                $table->unique('qbit_hash');
            });
        }
    }
};
