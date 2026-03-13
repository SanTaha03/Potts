<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('org_id')->constrained('orgs')->cascadeOnDelete();
            $table->foreignId('plant_type_id')->constrained('plant_types')->restrictOnDelete();
            $table->string('name');
            $table->enum('size', ['S', 'M', 'L'])->default('M');
            $table->json('location')->nullable();
            $table->date('installed_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['org_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plants');
    }
};
