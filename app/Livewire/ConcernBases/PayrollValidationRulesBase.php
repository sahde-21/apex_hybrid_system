<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\PayrollValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share PayrollValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class PayrollValidationRulesBase extends Component
{
    use PayrollValidationRules;
}
