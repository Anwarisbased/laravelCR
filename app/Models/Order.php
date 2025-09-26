<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'total',
        'is_redemption',
        'shipping_details',
        'billing_details'
    ];
    
    protected $casts = [
        'shipping_details' => 'array',
        'billing_details' => 'array',
        'is_redemption' => 'boolean',
        'total' => 'decimal:2'
    ];
    
    // Add methods to make it compatible with WooCommerce order interface
    public function set_address($address, $type = 'billing') {
        $address_data = [
            'first_name' => $address['first_name'] ?? '',
            'last_name' => $address['last_name'] ?? '',
            'address_1' => $address['address_1'] ?? '',
            'address_2' => $address['address_2'] ?? '',
            'city' => $address['city'] ?? '',
            'state' => $address['state'] ?? '',
            'postcode' => $address['postcode'] ?? '',
            'country' => $address['country'] ?? '',
            'email' => $address['email'] ?? '',
            'phone' => $address['phone'] ?? ''
        ];
        
        if ($type === 'shipping') {
            $this->shipping_details = $address_data;
        } else {
            $this->billing_details = $address_data;
        }
        
        $this->save();
    }
    
    public function add_product($product, $quantity = 1) {
        // For now, we'll just update the total and set the product_id
        // In a real implementation, you might want to store line items
        $this->product_id = $product->id;
        $this->total += $product->points_cost * $quantity;
        $this->save();
    }
    
    public function set_total($amount) {
        $this->total = $amount;
        $this->save();
    }
    
    public function update_meta_data($key, $value) {
        // For now, we'll store meta data in a JSON column
        // In a real implementation, you might want a separate meta table
        $meta = $this->meta_data ?? [];
        $meta[$key] = $value;
        $this->meta_data = $meta;
        $this->save();
    }
    
    public function get_id() {
        return $this->id;
    }
    
    public function update_status($new_status, $note = '') {
        $this->status = $new_status;
        $this->save();
        return $this; // Return $this for method chaining
    }
    
    public function save(array $options = []) {
        parent::save($options);
        return $this->id; // Return the ID like WooCommerce orders do
    }
    
    public function get_date_created() {
        return $this;
    }
    
    public function get_status() {
        return $this->status;
    }
    
    public function get_items() {
        return []; // Return empty array for now
    }
    
    public function date($format) {
        return $this->created_at->format($format); // Format the created_at date
    }
}
