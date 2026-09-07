<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\StockTransferValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share StockTransferValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class StockTransferValidationRulesBase extends Component
{
    use StockTransferValidationRules;
}
