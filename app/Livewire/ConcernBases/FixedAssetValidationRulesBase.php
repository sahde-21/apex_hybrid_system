<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\FixedAssetValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share FixedAssetValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class FixedAssetValidationRulesBase extends Component
{
    use FixedAssetValidationRules;
}
