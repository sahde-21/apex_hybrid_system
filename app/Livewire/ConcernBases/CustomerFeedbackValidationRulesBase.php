<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\CustomerFeedbackValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share CustomerFeedbackValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class CustomerFeedbackValidationRulesBase extends Component
{
    use CustomerFeedbackValidationRules;
}
