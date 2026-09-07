<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\LeadValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share LeadValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class LeadValidationRulesBase extends Component
{
    use LeadValidationRules;
}
