<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreJournalEntryRequest;
use App\Http\Requests\UpdateJournalEntryRequest;
use App\Models\JournalEntry;
use App\Services\JournalEntryService;
use Illuminate\Http\RedirectResponse;

class JournalEntryController extends Controller
{
    public function __construct(
        protected JournalEntryService $service,
    ) {}

    public function store(StoreJournalEntryRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('journal-entries.index')
            ->with('status', __('Journal entry created successfully.'));
    }

    public function update(UpdateJournalEntryRequest $request, JournalEntry $journalEntry): RedirectResponse
    {
        $this->service->update($journalEntry, $request->validated());

        return redirect()
            ->route('journal-entries.index')
            ->with('status', __('Journal entry updated successfully.'));
    }

    public function destroy(JournalEntry $journalEntry): RedirectResponse
    {
        $this->service->destroy($journalEntry);

        return redirect()
            ->route('journal-entries.index')
            ->with('status', __('Journal entry deleted successfully.'));
    }
}
