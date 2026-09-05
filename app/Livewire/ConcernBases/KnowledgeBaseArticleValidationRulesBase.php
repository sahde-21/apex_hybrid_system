<?php

namespace App\Livewire\ConcernBases;

use App\Concerns\KnowledgeBaseArticleValidationRules;
use Livewire\Component;

/**
 * Base component for Volt SFCs that share KnowledgeBaseArticleValidationRules.
 * Exists so PHPStan can analyse the trait via a real in-app consumer.
 */
abstract class KnowledgeBaseArticleValidationRulesBase extends Component
{
    use KnowledgeBaseArticleValidationRules;
}
