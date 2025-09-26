<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('sku')->index();
            $table->string('batch_id')->nullable()->index();
            $table->boolean('is_used')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_codes');
    }
};
