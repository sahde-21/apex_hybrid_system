@props([
    'label',
    'color' => 'zinc',
])

<flux:badge size="sm" :color="$color" {{ $attributes }}>
    {{ $label }}
</flux:badge>
