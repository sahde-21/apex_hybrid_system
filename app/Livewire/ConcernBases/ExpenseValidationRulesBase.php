<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\ExpenseValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share ExpenseValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class ExpenseValidationRulesBase extends Component
{
    use ExpenseValidationRules;
}
