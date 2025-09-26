<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('achievements', function (Blueprint $table) {
            $table->string('achievement_key')->primary();
            $table->string('type')->default('');
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('points_reward')->default(0);
            $table->string('rarity')->default('common');
            $table->string('icon_url')->default('');
            $table->boolean('is_active')->default(true);
            $table->string('trigger_event')->index();
            $table->unsignedInteger('trigger_count')->default(1);
            $table->json('conditions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
