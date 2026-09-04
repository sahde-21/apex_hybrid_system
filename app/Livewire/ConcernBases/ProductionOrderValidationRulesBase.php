<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\ProductionOrderValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share ProductionOrderValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class ProductionOrderValidationRulesBase extends Component
{
    use ProductionOrderValidationRules;
}
