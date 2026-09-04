<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\SupplierEvaluationValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share SupplierEvaluationValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class SupplierEvaluationValidationRulesBase extends Component
{
    use SupplierEvaluationValidationRules;
}
