<?php

namespace App\Livewire;

use App\Models\User;
use App\Services\Performance\GlobalSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public bool $open = false;

    /**
     * @return list<array{module: string, label: string, items: list<array{id: int, title: string, subtitle: string|null, url: string|null}>}>
     */
    #[Computed]
    public function results(): array
    {
        $user = auth()->user();

        if (! $user instanceof User || trim($this->query) === '') {
            return [];
        }

        return app(GlobalSearchService::class)->search($user, $this->query);
    }

    public function updatedQuery(): void
    {
        $this->open = mb_strlen(trim($this->query)) >= (int) config('performance.search.min_length', 2);
    }

    public function clear(): void
    {
        $this->reset('query', 'open');
    }

    public function render(): View
    {
        return view('livewire.global-search');
    }
}
