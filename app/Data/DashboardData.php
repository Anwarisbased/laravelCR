<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class DashboardData extends Data
{
    public function __construct(
        public int $lifetime_points,
    ) {
    }
    
    public static function fromServiceResponse(int $lifetime_points): self
    {
        try {
            return new self(
                lifetime_points: $lifetime_points
            );
        } catch (\Throwable $e) {
            throw new \App\Exceptions\DataTransformationException(
                'Service Response',
                self::class,
                $e->getMessage()
            );
        }
    }
}