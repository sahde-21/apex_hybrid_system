<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\PriceListValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share PriceListValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class PriceListValidationRulesBase extends Component
{
    use PriceListValidationRules;
}
