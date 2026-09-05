<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\AttendanceValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share AttendanceValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class AttendanceValidationRulesBase extends Component
{
    use AttendanceValidationRules;
}
