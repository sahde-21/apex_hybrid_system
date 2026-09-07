<?php

namespace App\Livewire\ConcernBases;

use App\Support\ScopesToPortalContact;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share ScopesToPortalContact.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class ScopesToPortalContactBase extends Component
{
    use ScopesToPortalContact;
}
