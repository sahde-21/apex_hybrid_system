<?php

namespace App\Enums\Analytics;

enum TrendDirection: string
{
    case StrongIncrease = 'strong_increase';
    case ModerateIncrease = 'moderate_increase';
    case Stable = 'stable';
    case ModerateDecrease = 'moderate_decrease';
    case StrongDecrease = 'strong_decrease';
    case InsufficientData = 'insufficient_data';
}
