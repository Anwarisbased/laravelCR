<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_code_generation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Who generated the codes
            $table->integer('quantity_generated'); // Number of codes generated in this session
            $table->string('session_identifier'); // Unique identifier for this generation session
            $table->json('qr_codes'); // Store the generated codes as JSON
            $table->timestamp('generated_at')->useCurrent(); // When the session was created
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_code_generation_sessions');
    }
};
