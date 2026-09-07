<?php

namespace App\Livewire\ConcernBases;

use App\Support\ScopesToSupplierContact;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share ScopesToSupplierContact.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class ScopesToSupplierContactBase extends Component
{
    use ScopesToSupplierContact;
}
