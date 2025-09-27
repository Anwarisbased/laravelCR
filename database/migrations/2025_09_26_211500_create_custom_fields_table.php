<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // e.g., favorite_strain
            $table->string('label');         // e.g., Favorite Strain
            $table->string('type');          // e.g., text, dropdown
            $table->json('options')->nullable(); // For dropdowns
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};