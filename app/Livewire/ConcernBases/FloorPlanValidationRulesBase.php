<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\FloorPlanValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share FloorPlanValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class FloorPlanValidationRulesBase extends Component
{
    use FloorPlanValidationRules;
}
