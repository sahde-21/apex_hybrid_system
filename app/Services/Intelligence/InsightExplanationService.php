<?php

namespace App\Services\Intelligence;

class InsightExplanationService
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function explain(string $type, array $context): array
    {
        return [
            'type' => $type,
            'what' => $context['what'] ?? __('scf.intelligence.explain_what_default'),
            'period' => $context['period'] ?? null,
            'formula' => $context['formula'] ?? null,
            'rule' => $context['rule'] ?? null,
            'limitations' => $context['limitations'] ?? [],
            'data_freshness' => $context['generated_at'] ?? now()->toIso8601String(),
            'confidence' => $context['confidence'] ?? 'medium',
            'value_type' => $context['value_type'] ?? 'actual',
            'advisory' => $context['advisory'] ?? false,
        ];
    }
}
