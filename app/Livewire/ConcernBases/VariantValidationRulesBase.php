<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\VariantValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share VariantValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class VariantValidationRulesBase extends Component
{
    use VariantValidationRules;
}
