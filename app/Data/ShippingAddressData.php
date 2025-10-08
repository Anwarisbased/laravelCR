<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ShippingAddressData extends Data
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $address1,
        public string $city,
        public string $state,
        public string $postcode,
    ) {
    }
    
    public static function fromShippingAddressDTO(\App\DTO\ShippingAddressDTO $dto): self
    {
        try {
            return new self(
                firstName: $dto->firstName,
                lastName: $dto->lastName,
                address1: $dto->address1,
                city: $dto->city,
                state: $dto->state,
                postcode: $dto->postcode
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                \App\DTO\ShippingAddressDTO::class,
                self::class,
                $e->getMessage()
            );
        }
    }
    
    public static function createEmpty(): self
    {
        try {
            return new self(
                firstName: '',
                lastName: '',
                address1: '',
                city: '',
                state: '',
                postcode: ''
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                'Empty Values',
                self::class,
                $e->getMessage()
            );
        }
    }
}