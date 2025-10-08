<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
    
    public function getDisplayName(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::SHIPPED => 'Shipped',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::REFUNDED => 'Refunded',
        };
    }
    
    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::SHIPPED => 'primary',
            self::DELIVERED => 'success',
            self::CANCELLED => 'danger',
            self::REFUNDED => 'secondary',
        };
    }
}