<?php

namespace App\Enums\Analytics;

enum InsightStatus: string
{
    case Active = 'active';
    case Acknowledged = 'acknowledged';
    case Dismissed = 'dismissed';
    case Expired = 'expired';
}
