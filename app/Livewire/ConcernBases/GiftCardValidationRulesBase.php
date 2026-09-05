<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\GiftCardValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share GiftCardValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class GiftCardValidationRulesBase extends Component
{
    use GiftCardValidationRules;
}
