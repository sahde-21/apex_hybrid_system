<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\JournalEntryValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share JournalEntryValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class JournalEntryValidationRulesBase extends Component
{
    use JournalEntryValidationRules;
}
