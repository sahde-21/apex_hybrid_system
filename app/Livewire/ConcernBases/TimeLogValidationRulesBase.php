<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\TimeLogValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share TimeLogValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class TimeLogValidationRulesBase extends Component
{
    use TimeLogValidationRules;
}
