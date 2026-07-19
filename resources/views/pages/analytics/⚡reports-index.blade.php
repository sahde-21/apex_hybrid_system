<?php

use App\Services\Bi\BiReportService;
use App\Support\Bi\BiFilter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('BI Reports')] class extends Component {
    #[Url]
    public string $type = 'executive';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mount(): void
    {
        $this->authorize('analytics.read');
        $this->from = $this->from ?: now()->startOfMonth()->toDateString();
        $this->to = $this->to ?: now()->toDateString();
    }

    public function updated(): void
    {
        unset($this->report, $this->available);
    }

    protected function filter(): BiFilter
    {
        return BiFilter::fromArray([
            'from' => $this->from,
            'to' => $this->to,
            'dashboard' => 'ceo',
        ]);
    }

    #[Computed]
    public function available()
    {
        return app(BiReportService::class)->availableReports(auth()->user());
    }

    #[Computed]
    public function report(): array
    {
        return app(BiReportService::class)->report(auth()->user(), $this->type, $this->filter());
    }
}; ?>

<section class="scf-page space-y-6">
    <div class="portal-glass rounded-2xl p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('scf.bi.reports') }}</flux:heading>
                <flux:subheading>{{ __('scf.bi.reports_subtitle') }}</flux:subheading>
            </div>
            <flux:button :href="route('analytics.hub')" variant="ghost" icon="arrow-left" wire:navigate>
                {{ __('scf.bi.back_to_hub') }}
            </flux:button>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-3">
            <flux:select wire:model.live="type" :label="__('scf.bi.report_type')">
                @foreach ($this->available as $item)
                    <option value="{{ $item['key'] }}">{{ $item['label'] }}</option>
                @endforeach
            </flux:select>
            <flux:input type="date" wire:model.live="from" :label="__('scf.bi.filter_from')" />
            <flux:input type="date" wire:model.live="to" :label="__('scf.bi.filter_to')" />
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
            <flux:button size="sm" :href="route('analytics.export.csv', ['type' => $type, 'from' => $from, 'to' => $to])" variant="ghost" icon="arrow-down-tray">{{ __('scf.bi.export_csv') }}</flux:button>
            <flux:button size="sm" :href="route('analytics.export.excel', ['type' => $type, 'from' => $from, 'to' => $to])" variant="ghost" icon="table-cells">{{ __('scf.bi.export_excel') }}</flux:button>
            <flux:button size="sm" :href="route('analytics.export.pdf', ['type' => $type, 'from' => $from, 'to' => $to])" variant="ghost" icon="document">{{ __('scf.bi.export_pdf') }}</flux:button>
            <flux:button size="sm" :href="route('analytics.export.print', ['type' => $type, 'from' => $from, 'to' => $to])" variant="ghost" icon="printer" target="_blank">{{ __('scf.bi.export_print') }}</flux:button>
        </div>
    </div>

    <div class="portal-glass overflow-hidden rounded-2xl">
        <div class="border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
            <flux:heading size="sm">{{ $this->report['title'] }}</flux:heading>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        @foreach ($this->report['headers'] as $header)
                            <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-300">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($this->report['rows'] as $row)
                        <tr>
                            @foreach ($row as $cell)
                                <td class="px-4 py-3 tabular-nums text-zinc-800 dark:text-zinc-100">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-8 text-center text-zinc-500" colspan="{{ max(1, count($this->report['headers'])) }}">
                                {{ __('scf.bi.no_rows') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
