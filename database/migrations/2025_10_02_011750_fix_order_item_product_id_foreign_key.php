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
        // Drop the existing foreign key constraint
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        
        // Recreate the foreign key constraint without the 'onDelete set null' behavior
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraint
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });
        
        // Recreate the original foreign key constraint
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }
};
