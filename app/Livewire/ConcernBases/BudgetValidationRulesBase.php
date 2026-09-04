<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\BudgetValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share BudgetValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class BudgetValidationRulesBase extends Component
{
    use BudgetValidationRules;
}
