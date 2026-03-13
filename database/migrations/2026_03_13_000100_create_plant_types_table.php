<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('display_name');
            $table->string('category');
            $table->text('description')->nullable();

            $table->unsignedTinyInteger('soil_opt_min');
            $table->unsignedTinyInteger('soil_opt_max');
            $table->decimal('temp_opt_min', 5, 2);
            $table->decimal('temp_opt_max', 5, 2);
            $table->unsignedTinyInteger('light_opt_min');
            $table->unsignedTinyInteger('light_opt_max');

            $table->decimal('co2_k_per_m2_year', 8, 3)->default(1.700);
            $table->decimal('leaf_area_m2_s', 8, 3);
            $table->decimal('leaf_area_m2_m', 8, 3);
            $table->decimal('leaf_area_m2_l', 8, 3);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_types');
    }
};
