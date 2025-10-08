<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                // Generate a unique order number
                $order->order_number = 'CR-' . strtoupper(uniqid());
            }
        });
    }
    
    protected $fillable = [
        'user_id',
        'order_number',
        'status',
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
        'notes',
        'meta_data',
    ];
    
    protected $casts = [
        'points_cost' => 'integer',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'is_canna_redemption' => 'boolean',
        'meta_data' => 'array',
    ];
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    
    // Accessors
    public function getOrderNumberAttribute($value)
    {
        return $value ?? 'CR-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }
    
    public function getStatusAttribute($value)
    {
        return $value ?? 'processing';
    }
    
    // Scopes
    public function scopeRedemptions($query)
    {
        return $query->where('is_canna_redemption', true);
    }
    
    public function scopeByUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
    
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
    
    // Methods
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }
    
    public function getFormattedShippingAddressAttribute(): string
    {
        $parts = [
            $this->shipping_first_name . ' ' . $this->shipping_last_name,
            $this->shipping_address_1,
            $this->shipping_address_2,
            $this->shipping_city . ', ' . $this->shipping_state . ' ' . $this->shipping_postcode,
            $this->shipping_country,
        ];
        
        return implode("\n", array_filter($parts));
    }
    
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }
    
    public function markAsShipped(string $trackingNumber = null): void
    {
        $this->update([
            'status' => 'shipped',
            'shipped_at' => now(),
            'tracking_number' => $trackingNumber,
        ]);
        
        event(new \App\Events\OrderShipped($this));
    }
}