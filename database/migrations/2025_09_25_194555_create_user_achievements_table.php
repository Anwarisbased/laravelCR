<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('achievement_key');
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['user_id', 'achievement_key']);
            $table->foreign('achievement_key')->references('achievement_key')->on('achievements')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
