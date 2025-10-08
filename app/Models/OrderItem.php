<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'points_value',
        'meta_data',
    ];
    
    protected $casts = [
        'quantity' => 'integer',
        'points_value' => 'integer',
        'meta_data' => 'array',
    ];
    
    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    // Methods
    public function getFormattedProductNameAttribute(): string
    {
        return $this->product_name ?? $this->product?->name ?? 'Unknown Product';
    }
}