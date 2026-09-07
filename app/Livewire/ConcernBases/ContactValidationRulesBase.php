<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\ContactValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share ContactValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class ContactValidationRulesBase extends Component
{
    use ContactValidationRules;
}
