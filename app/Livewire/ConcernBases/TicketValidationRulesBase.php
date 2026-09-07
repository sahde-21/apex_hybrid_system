<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\TicketValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share TicketValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class TicketValidationRulesBase extends Component
{
    use TicketValidationRules;
}
