<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\ShippingMethodValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share ShippingMethodValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class ShippingMethodValidationRulesBase extends Component
{
    use ShippingMethodValidationRules;
}
