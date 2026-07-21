<?php

namespace App\Enums\Analytics;

enum HealthScoreLabel: string
{
    case Excellent = 'excellent';
    case Healthy = 'healthy';
    case Stable = 'stable';
    case NeedsAttention = 'needs_attention';
    case HighRisk = 'high_risk';
    case InsufficientData = 'insufficient_data';
}
