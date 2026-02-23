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
        Schema::table('devices', function (Blueprint $table) {
            // Renaming
            // Note: Make sure to install doctrine/dbal if using SQLite < 3.35 or older MySQL for renaming
            // However, Laravel 11/Modern usually handles it. 
            // If serial exists, rename to device_id (External ID)
            if (Schema::hasColumn('devices', 'serial')) {
                $table->renameColumn('serial', 'device_id');
            }
            if (Schema::hasColumn('devices', 'alias')) {
                $table->renameColumn('alias', 'name');
            }

            // New columns
            if (!Schema::hasColumn('devices', 'token')) {
                $table->string('token', 80)->after('status')->nullable()->unique()->comment('Secret token for API authentication');
            }
            if (!Schema::hasColumn('devices', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable()->after('token');
            }
            if (!Schema::hasColumn('devices', 'last_values')) {
                $table->json('last_values')->nullable()->after('last_seen_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn(['token', 'last_seen_at', 'last_values']);
            
            // Rename back
            $table->renameColumn('device_id', 'serial');
            $table->renameColumn('name', 'alias');
        });
    }
};
