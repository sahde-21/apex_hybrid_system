<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\FinancialReportValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share FinancialReportValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class FinancialReportValidationRulesBase extends Component
{
    use FinancialReportValidationRules;
}
