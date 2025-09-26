<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triggers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event_key')->index();
            $table->string('action_type');
            $table->string('action_value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triggers');
    }
};
