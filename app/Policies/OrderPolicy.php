<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    public function view(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    public function update(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }

    public function delete(User $user, Order $order): bool
    {
        return $order->user_id === $user->id;
    }
    
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own order history
    }
}