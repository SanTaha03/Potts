<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::create('devices', function (Blueprint $table) {
      $table->id();
      $table->foreignId('org_id')->constrained('orgs')->cascadeOnDelete();
      $table->string('serial')->unique();
      $table->string('alias')->nullable();
      $table->string('status')->default('active'); // active|inactive|archived
      $table->json('location')->nullable();       // site/étage/zone
      $table->timestamps();
    });
  }
  public function down(): void { Schema::dropIfExists('devices'); }
};
