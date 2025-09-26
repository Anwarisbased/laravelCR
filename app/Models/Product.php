<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'description',
        'points_award',
        'points_cost',
        'required_rank',
        'meta'
    ];
    
    protected $casts = [
        'meta' => 'array'
    ];
    
    public function get_id() {
        return $this->id;
    }
    
    public function get_sku() {
        return $this->sku;
    }
    
    public function get_name() {
        return $this->name;
    }
    
    public function get_meta($key) {
        $meta = $this->meta ?? [];
        return $meta[$key] ?? null;
    }
    
    public function get_image() {
        // Return a placeholder image URL
        return '/images/placeholder.png';
    }
}
