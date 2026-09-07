<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\DeliveryTripValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share DeliveryTripValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class DeliveryTripValidationRulesBase extends Component
{
    use DeliveryTripValidationRules;
}
