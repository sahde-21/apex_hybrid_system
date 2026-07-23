@props([
    'title' => null,
    'description' => null,
])

<x-empty-state
    icon="lock-closed"
    :title="$title ?? __('scf.errors.no_permission_title')"
    :description="$description ?? __('scf.errors.no_permission_message')"
    {{ $attributes }}
/>
