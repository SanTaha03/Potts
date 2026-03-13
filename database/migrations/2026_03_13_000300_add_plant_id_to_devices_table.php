<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (! Schema::hasColumn('devices', 'plant_id')) {
                $table->foreignId('plant_id')
                    ->nullable()
                    ->after('org_id')
                    ->constrained('plants')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            if (Schema::hasColumn('devices', 'plant_id')) {
                $table->dropConstrainedForeignId('plant_id');
            }
        });
    }
};
