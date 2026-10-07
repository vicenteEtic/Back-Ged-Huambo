<?php

namespace App\Enum;

enum BenefitCategory: string
{
    case Subsidy = 'subsidy';
    case Medical = 'medical';
    case HealthInsurance = 'health_insurance';
    case SocialSupport = 'social_support';
    case Institutional = 'institutional';
    case Other = 'other';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
