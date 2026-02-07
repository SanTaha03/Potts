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
        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type'); // maintenance, replacement, installation
            $table->string('title');
            $table->string('address');
            $table->string('status')->default('planned'); // planned, in_progress, done, cancelled
            $table->dateTime('scheduled_for');
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mission_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // check, water, replace, install
            $table->string('status')->default('todo'); // todo, done, skipped
            $table->json('meta')->nullable(); // pour stocker old/new device, location, etc.
            $table->timestamps();
        });

        Schema::create('mission_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('mission_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mission_id')->constrained('missions')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('severity')->default('medium'); // low, medium, high
            $table->string('type'); // access_problem, broken_pot, plant_dead, other
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mission_incidents');
        Schema::dropIfExists('mission_notes');
        Schema::dropIfExists('mission_items');
        Schema::dropIfExists('missions');
    }
};
