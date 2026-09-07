<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\ProfileValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share ProfileValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class ProfileValidationRulesBase extends Component
{
    use ProfileValidationRules;
}
