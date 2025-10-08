<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Commands\RedeemRewardCommand;
use App\Domain\ValueObjects\UserId;
use App\Domain\ValueObjects\ProductId;

class RedeemRewardRequest extends FormRequest
{
    public function authorize(): bool 
    { 
        return true; 
    }

    public function rules(): array
    {
        return [
            'productId' => 'required|integer',
            'shippingDetails.first_name' => 'required|string',
            'shippingDetails.last_name' => 'required|string',
            'shippingDetails.address1' => 'required|string',
            'shippingDetails.city' => 'required|string',
            'shippingDetails.state' => 'required|string',
            'shippingDetails.postcode' => 'required|string',
        ];
    }

    public function toCommand(): RedeemRewardCommand
    {
        $validated = $this->validated();
        return new RedeemRewardCommand(
            UserId::fromInt($this->user()->id),
            ProductId::fromInt($validated['productId']),
            $validated['shippingDetails']
        );
    }
}