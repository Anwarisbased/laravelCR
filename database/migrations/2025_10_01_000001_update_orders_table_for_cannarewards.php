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
        Schema::table('orders', function (Blueprint $table) {
            // Add the new columns required for CannaRewards if they don't exist
            if (!Schema::hasColumn('orders', 'order_number')) {
                $table->string('order_number')->nullable()->after('id');
            }
            
            if (!Schema::hasColumn('orders', 'points_cost')) {
                $table->integer('points_cost')->default(0)->after('status');
            }
            
            if (!Schema::hasColumn('orders', 'shipping_first_name')) {
                $table->string('shipping_first_name')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'shipping_last_name')) {
                $table->string('shipping_last_name')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'shipping_address_1')) {
                $table->string('shipping_address_1')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'shipping_address_2')) {
                $table->string('shipping_address_2')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'shipping_city')) {
                $table->string('shipping_city')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'shipping_state')) {
                $table->string('shipping_state')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'shipping_postcode')) {
                $table->string('shipping_postcode')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'shipping_country')) {
                $table->string('shipping_country')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'shipping_phone')) {
                $table->string('shipping_phone')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'tracking_number')) {
                $table->string('tracking_number')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'shipped_at')) {
                $table->timestamp('shipped_at')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable();
            }
            
            if (!Schema::hasColumn('orders', 'is_canna_redemption')) {
                $table->boolean('is_canna_redemption')->default(true);
            }
            
            if (!Schema::hasColumn('orders', 'notes')) {
                $table->text('notes')->nullable();
            }
            
            // Update existing status column to use proper enum values if needed
            $table->string('status', 20)->default('processing')->change();
        });
        
        // Update the order_number for existing records
        \DB::statement("UPDATE orders SET order_number = CONCAT('CR-', LPAD(id, 6, '0')) WHERE (order_number IS NULL OR order_number = '') AND id IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'order_number',
                'points_cost',
                'shipping_first_name',
                'shipping_last_name',
                'shipping_address_1',
                'shipping_address_2',
                'shipping_city',
                'shipping_state',
                'shipping_postcode',
                'shipping_country',
                'shipping_phone',
                'tracking_number',
                'shipped_at',
                'delivered_at',
                'is_canna_redemption',
                'notes'
            ]);
            
            // Revert the status column if needed
            $table->string('status')->default('pending')->change();
        });
    }
};