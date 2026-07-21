<?php

namespace App\Livewire;

use App\Services\Performance\GlobalSearchService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public bool $open = false;

    #[Computed]
    public function results(): array
    {
        $user = auth()->user();

        if ($user === null || trim($this->query) === '') {
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

    public function render()
    {
        return view('livewire.global-search');
    }
}
