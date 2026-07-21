<?php

namespace App\Enums\Analytics;

enum InsightSeverity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}
