<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            $table->dropUnique('downloads_qbit_hash_unique');
            $table->index('qbit_hash');
        });
    }

    public function down(): void
    {
        Schema::table('downloads', function (Blueprint $table) {
            $table->dropIndex('downloads_qbit_hash_index');
            $table->unique('qbit_hash');
        });
    }
};
