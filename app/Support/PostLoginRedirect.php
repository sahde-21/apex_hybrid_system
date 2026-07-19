<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class PostLoginRedirect
{
    /**
     * Preferred landing routes after authentication, ordered by priority.
     *
     * @var array<int, array{route: string, permission: string}>
     */
    public const CANDIDATES = [
        ['route' => 'dashboard', 'permission' => 'dashboard.read'],
        ['route' => 'pos.terminal', 'permission' => 'pos.read'],
        ['route' => 'sale-orders.index', 'permission' => 'sale-orders.read'],
        ['route' => 'invoices.index', 'permission' => 'invoices.read'],
        ['route' => 'products.index', 'permission' => 'products.read'],
        ['route' => 'purchase-orders.index', 'permission' => 'purchase-orders.read'],
        ['route' => 'employees.index', 'permission' => 'employees.read'],
        ['route' => 'expenses.index', 'permission' => 'expenses.read'],
        ['route' => 'contacts.index', 'permission' => 'contacts.read'],
        ['route' => 'tickets.index', 'permission' => 'tickets.read'],
        ['route' => 'profile.edit', 'permission' => ''],
    ];

    public static function url(?User $user = null): string
    {
        $user ??= auth()->user();

        if ($user === null) {
            return route('login');
        }

        foreach (self::CANDIDATES as $candidate) {
            if (! Route::has($candidate['route'])) {
                continue;
            }

            if ($candidate['permission'] !== '' && ! $user->can($candidate['permission'])) {
                continue;
            }

            return route($candidate['route']);
        }

        return route('profile.edit');
    }
}
