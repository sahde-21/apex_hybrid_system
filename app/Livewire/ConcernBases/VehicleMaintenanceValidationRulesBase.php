<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\VehicleMaintenanceValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share VehicleMaintenanceValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class VehicleMaintenanceValidationRulesBase extends Component
{
    use VehicleMaintenanceValidationRules;
}
