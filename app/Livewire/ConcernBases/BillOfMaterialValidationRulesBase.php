<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\BillOfMaterialValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share BillOfMaterialValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class BillOfMaterialValidationRulesBase extends Component
{
    use BillOfMaterialValidationRules;
}
