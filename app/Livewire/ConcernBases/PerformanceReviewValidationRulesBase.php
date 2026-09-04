<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\PerformanceReviewValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share PerformanceReviewValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class PerformanceReviewValidationRulesBase extends Component
{
    use PerformanceReviewValidationRules;
}
