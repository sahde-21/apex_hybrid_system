<?php

namespace App\Support\Analytics;

use App\Models\User;

trait ScopesAnalytics
{
    protected function canView(User $user, string $permission): bool
    {
        return $user->can($permission) || $user->can('intelligence.view');
    }

    protected function requirePermission(User $user, string $permission): void
    {
        abort_unless($this->canView($user, $permission), 403);
    }

    /**
     * @return array<string, mixed>
     */
    protected function metadata(string $label, ?string $warning = null): array
    {
        return [
            'generated_at' => now()->toIso8601String(),
            'label' => $label,
            'is_estimate' => false,
            'data_quality' => $warning ? 'warning' : 'ok',
            'warnings' => array_filter([$warning]),
        ];
    }
}
