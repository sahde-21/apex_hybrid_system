<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\BankReconciliationValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share BankReconciliationValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class BankReconciliationValidationRulesBase extends Component
{
    use BankReconciliationValidationRules;
}
