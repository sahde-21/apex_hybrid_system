@props(['type', 'id'])

<flux:dropdown position="bottom" align="end">
    <flux:button size="sm" variant="ghost" icon="printer" :title="__('scf.print')" />
    <flux:menu>
        <flux:menu.item
            :href="route('print.document', ['type' => $type, 'id' => $id, 'layout' => 'a4'])"
            target="_blank"
            icon="document-text"
        >
            {{ __('scf.print_a4') }}
        </flux:menu.item>
        <flux:menu.item
            :href="route('print.document', ['type' => $type, 'id' => $id, 'layout' => 'thermal_80mm'])"
            target="_blank"
            icon="receipt-percent"
        >
            {{ __('scf.print_thermal') }}
        </flux:menu.item>
    </flux:menu>
</flux:dropdown>
