<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\TaxRateValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share TaxRateValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class TaxRateValidationRulesBase extends Component
{
    use TaxRateValidationRules;
}
