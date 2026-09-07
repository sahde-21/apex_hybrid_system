<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\LeaveRequestValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share LeaveRequestValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class LeaveRequestValidationRulesBase extends Component
{
    use LeaveRequestValidationRules;
}
