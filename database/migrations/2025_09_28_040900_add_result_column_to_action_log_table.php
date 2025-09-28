<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canna_user_action_log', function (Blueprint $table) {
            $table->boolean('result')->default(true)->after('object_id');
        });
    }

    public function down(): void
    {
        Schema::table('canna_user_action_log', function (Blueprint $table) {
            $table->dropColumn(['result']);
        });
    }
};