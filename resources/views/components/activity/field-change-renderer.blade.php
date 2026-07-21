@props([
    'changes' => [],
])

<div class="overflow-hidden rounded-lg border border-zinc-100 dark:border-zinc-800">
    <table class="w-full text-start text-xs">
        <thead class="bg-zinc-50 text-zinc-500 dark:bg-zinc-800/60">
            <tr>
                <th class="px-3 py-2 font-medium">{{ __('scf.activity.field') }}</th>
                <th class="px-3 py-2 font-medium">{{ __('scf.activity.previous') }}</th>
                <th class="px-3 py-2 font-medium">{{ __('scf.activity.new') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @foreach ($changes as $change)
                <tr>
                    <td class="px-3 py-2 font-medium text-zinc-700 dark:text-zinc-200">{{ $change['label'] }}</td>
                    <td class="px-3 py-2 text-zinc-500">{{ is_scalar($change['old'] ?? null) || $change['old'] === null ? ($change['old'] ?? '—') : json_encode($change['old']) }}</td>
                    <td class="px-3 py-2 text-zinc-800 dark:text-zinc-100">{{ is_scalar($change['new'] ?? null) || $change['new'] === null ? ($change['new'] ?? '—') : json_encode($change['new']) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
