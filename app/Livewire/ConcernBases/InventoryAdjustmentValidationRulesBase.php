<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\InventoryAdjustmentValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share InventoryAdjustmentValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class InventoryAdjustmentValidationRulesBase extends Component
{
    use InventoryAdjustmentValidationRules;
}
