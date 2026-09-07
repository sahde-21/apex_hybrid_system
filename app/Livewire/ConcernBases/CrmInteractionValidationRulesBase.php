<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\CrmInteractionValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share CrmInteractionValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class CrmInteractionValidationRulesBase extends Component
{
    use CrmInteractionValidationRules;
}
