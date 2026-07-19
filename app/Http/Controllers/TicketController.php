<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $service,
    ) {}

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $this->service->store($request->validated());

        return redirect()
            ->route('tickets.index')
            ->with('status', __('Tickets created successfully.'));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->service->update($ticket, $request->validated());

        return redirect()
            ->route('tickets.index')
            ->with('status', __('Tickets updated successfully.'));
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->service->destroy($ticket);

        return redirect()
            ->route('tickets.index')
            ->with('status', __('Tickets deleted successfully.'));
    }
}
