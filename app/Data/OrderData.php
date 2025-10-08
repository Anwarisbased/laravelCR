<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

#[MapName(SnakeCaseMapper::class)]
class OrderData extends Data
{
    public function __construct(
        public int $id,
        #[MapName('order_number')]
        #[Validation(['required', 'string', 'max:50'])]
        public string $orderNumber,
        #[Validation(['required', 'string', 'max:50', 'in:pending,processing,shipped,delivered,cancelled'])]
        public string $status,
        #[MapName('status_display')]
        #[Validation(['required', 'string', 'max:50'])]
        public string $statusDisplay,
        #[MapName('points_cost')]
        #[Validation(['integer', 'min:0'])]
        public int $pointsCost,
        #[Validation(['array'])]
        public ?array $items = [],
        #[MapName('shipping_address')]
        public ?array $shippingAddress = null,
        #[MapName('tracking_number')]
        #[Validation(['nullable', 'string', 'max:100'])]
        public ?string $trackingNumber = null,
        #[MapName('shipped_at')]
        #[Validation(['nullable', 'date'])]
        public ?string $shippedAt = null,
        #[MapName('delivered_at')]
        #[Validation(['nullable', 'date'])]
        public ?string $deliveredAt = null,
        #[MapName('created_at')]
        public ?string $createdAt = null,
        #[MapName('updated_at')]
        public ?string $updatedAt = null,
        #[MapName('is_canna_redemption')]
        #[Validation(['boolean'])]
        public ?bool $isCannaRedemption = null,
        #[Validation(['nullable', 'string', 'max:1000'])]
        public ?string $notes = null,
        #[MapName('meta_data')]
        #[Validation(['array'])]
        public ?array $metaData = [],
    ) {
    }

    public static function fromModel(\App\Models\Order $order): self
    {
        try {
            return new self(
                id: $order->id,
                orderNumber: $order->order_number,
                status: $order->status,
                statusDisplay: ucfirst($order->status),
                pointsCost: $order->points_cost,
                items: $order->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product_name,
                        'product_sku' => $item->product_sku,
                        'quantity' => $item->quantity,
                        'points_value' => $item->points_value,
                        'product_image' => $item->product_image,
                    ];
                })->toArray(),
                shippingAddress: [
                    'first_name' => $order->shipping_first_name,
                    'last_name' => $order->shipping_last_name,
                    'address_1' => $order->shipping_address_1,
                    'address_2' => $order->shipping_address_2,
                    'city' => $order->shipping_city,
                    'state' => $order->shipping_state,
                    'postcode' => $order->shipping_postcode,
                    'country' => $order->shipping_country,
                    'phone' => $order->shipping_phone,
                ],
                trackingNumber: $order->tracking_number,
                shippedAt: $order->shipped_at,
                deliveredAt: $order->delivered_at,
                createdAt: $order->created_at,
                updatedAt: $order->updated_at,
                isCannaRedemption: $order->is_canna_redemption,
                notes: $order->notes,
                metaData: $order->meta_data,
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                \App\Models\Order::class,
                self::class,
                $e->getMessage()
            );
        }
    }
}