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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('invitee_user_id')->constrained('users')->onDelete('cascade');
            $table->string('referral_code');
            $table->enum('status', ['signed_up', 'pending', 'converted'])->default('pending');
            $table->timestamp('converted_at')->nullable();
            $table->integer('bonus_points_awarded')->default(0);
            $table->timestamps();
            
            $table->index(['referral_code']);
            $table->index(['referrer_user_id']);
            $table->index(['invitee_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
