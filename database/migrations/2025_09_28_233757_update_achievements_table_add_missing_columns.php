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
        Schema::table('achievements', function (Blueprint $table) {
            // Add description column if it doesn't exist
            if (!Schema::hasColumn('achievements', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            
            // Add sort_order column if it doesn't exist
            if (!Schema::hasColumn('achievements', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('trigger_count');
            }
            
            // Add category column if it doesn't exist
            if (!Schema::hasColumn('achievements', 'category')) {
                $table->string('category')->default('')->after('sort_order');
            }
            
            // Add type column if it doesn't exist
            if (!Schema::hasColumn('achievements', 'type')) {
                $table->string('type')->default('')->after('category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('achievements', function (Blueprint $table) {
            $table->dropColumn(['description', 'sort_order', 'category', 'type']);
        });
    }
};
