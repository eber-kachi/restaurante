<?php

namespace App\Enums;

enum DeliveryFeeType: string
{
    case FIXED = 'fixed';
    case TIERED = 'tiered';
    case PER_DISTANCE = 'per_distance';

    public static function labels(): array
    {
        return [
            self::FIXED->value => 'Tarifa fija',
            self::TIERED->value => 'Tramos de distancia',
            self::PER_DISTANCE->value => 'Tarifa por distancia'
        ];
    }
}
